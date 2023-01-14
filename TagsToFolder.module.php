<?php namespace ProcessWire;

/**
 * Convert tags into folders in the ajax nav menu
 * 
 * Copyright (c) 2023 EPRC
 * Licensed under MIT License, see LICENSE
 *
 * https://eprc.studio
 *
 * For ProcessWire 3.x
 * Copyright (c) 2021 by Ryan Cramer
 * Licensed under GNU/GPL v2
 *
 * https://www.processwire.com
 *
 */
class TagsToFolder extends WireData implements Module, ConfigurableModule {

	public function ready() {
		$path = "{$this->config->paths->$this}{$this}.css";
		$url = "{$this->config->urls->$this}{$this}.css";
		$this->config->styles->add("$url?v=" . filemtime($path));
		$this->addHookAfter("ProcessTemplate::executeNavJSON", $this, "manipulateTemplateMenu");
		$this->addHookAfter("ProcessField::executeNavJSON", $this, "manipulateTemplateMenu");
	}

	public function manipulateTemplateMenu(HookEvent $event) {
		$type = $event->object->getModuleInfo()["title"] === "Templates" ? "templates" : "fields";
		$data = json_decode($event->return, true);
		if($tag = $this->input->get("tag")) {
			unset($data["add"]);
			foreach($data["list"] as $key => $info) {
				$id = trim(strstr($info["url"], "id="), "id=");
				$item = $this->{$type}->get($id);
				if(!$item->hasTag($tag)) {
					unset($data["list"][$key]);
					continue;
				}
			}
		} else {
			$untagged = array();
			$tags = array();
			foreach($data["list"] as $info) {
				$id = trim(strstr($info["url"], "id="), "id=");
				$item = $this->{$type}->get($id);
				if(!strlen($item->tags)) {
					$untagged[] = $info;
					continue;
				}
				foreach($item->getTags() as $tag) {
					if(empty($tag)) continue;
					$tag = ltrim($tag, '-');
					if(!in_array($tag, $tags)) $tags[] = $tag;
				}
			}
			sort($tags);
			$data["list"] = array();
			foreach($tags as $tag) {
				$data["list"][] = array(
					"url" => $data["url"],
					"label" => $tag,
					"icon" => "tags",
					"className" => "tag",
					"navJSON" => "{$data["url"]}navJSON?tag=$tag",
				);
			}
			$data["list"] = array_merge($data["list"], $untagged);
		}
		$data['list'] = array_values($data['list']); 
		$event->return = json_encode($data);
	}
}