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
class TagsToFolders extends WireData implements Module {

	public function ready() {
		$path = "{$this->config->paths->$this}{$this}.css";
		$url = "{$this->config->urls->$this}{$this}.css";
		$this->config->styles->add("$url?v=" . filemtime($path));
		$this->addHookAfter("ProcessTemplate::executeNavJSON", $this, "manipulateMenu");
		$this->addHookAfter("ProcessField::executeNavJSON", $this, "manipulateMenu");
		// $this->addHookBefore("ProcessUser::executeNavJSON", $this, "manipulateUserMenu");
	}

	public function manipulateMenu(HookEvent $event) {
		$type = $event->object->getModuleInfo()["title"] === "Templates" ? "templates" : "fields";
		$data = json_decode($event->return, true);
		if($tag = $this->input->get("tag")) {
			unset($data["add"]);
			foreach($data["list"] as $key => $info) {
				$id = trim(strstr($info["url"], "id="), "id=");
				$item = $this->{$type}->get($id);
				if(
					($tag === "system" && !$this->isSystem($item))
					|| ($tag !== "system" && !$item->hasTag($tag))
				) {
					unset($data["list"][$key]);
				}
			}
		} else {
			$untagged = array();
			$tags = array();
			foreach($data["list"] as $info) {
				$id = trim(strstr($info["url"], "id="), "id=");
				$item = $this->{$type}->get($id);
				if($this->isSystem($item)) {
					if(!in_array("system", $tags)) $tags[] = "system";
					continue;
				} elseif(!strlen($item->tags)) {
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
					"icon" => $tag === "system" ? "gear" : "tags",
					"className" => "tag",
					"navJSON" => "$data[url]navJSON?tag=$tag",
				);
			}
			$data["list"] = array_merge($data["list"], $untagged);
		}
		$data['list'] = array_values($data['list']); 
		$event->return = json_encode($data);
	}

	private function isSystem($item) {
		if(!$class = wireInstanceOf($item, ["Template", "Field"])) return false;
		if($class == "ProcessWire\Field" && in_array($item->name, array('title', 'email'))) return false;
		return $item->flags & ($class == "ProcessWire\Template" ? Template::flagSystem : Field::flagSystem);
	}

	public function manipulateUserMenu(HookEvent $event) {
		// todo, see /wire/modules/Process/ProcessUser/ProcessUser.module#L77
	}
}