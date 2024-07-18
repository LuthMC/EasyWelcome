<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;

class SimpleWelcome {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function handlePlayerJoin(Player $player): void {
        $config = $this->plugin->getConfig();
        $enabled = $config->get("enabled", true);
        if (!$enabled) {
            return;
        }

        $playerName = $player->getName();
        $playerPing = $player->getNetworkSession()->getPing();
        $x = round($player->getPosition()->getX());
        $y = round($player->getPosition()->getY());
        $z = round($player->getPosition()->getZ());
        $onlineCount = count($this->plugin->getServer()->getOnlinePlayers());
        $worldName = $player->getWorld()->getDisplayName();

        $teleportConfig = $config->get("teleport");
        $teleportEnabled = $teleportConfig["enabled"];
        if ($teleportEnabled) {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($teleportConfig["world"]);
            if ($world !== null) {
                $position = new Position($teleportConfig["x"], $teleportConfig["y"], $teleportConfig["z"], $world);
                $player->teleport($position);
                $x = round($position->getX());
                $y = round($position->getY());
                $z = round($position->getZ());
                $worldName = $world->getDisplayName();
            } else {
                $this->plugin->getLogger()->warning("World '{$teleportConfig["world"]}' not found. Teleportation failed.");
            }
        }

        $tags = ["{name}", "{ping}", "{x}", "{y}", "{z}", "{online}", "{world_name}"];
        $values = [$playerName, $playerPing, $x, $y, $z, $onlineCount, $worldName];
        $messages = $config->get("messages");
        $title = str_replace($tags, $values, $messages["title"]);
        $subtitle = str_replace($tags, $values, $messages["subtitle"]);
        $auctionbar = str_replace($tags, $values, $messages["auctionbar"]);
        $joinMessage = str_replace($tags, $values, $messages["join_message"]);

        $this->plugin->sendTitle($player, $title, $subtitle);
        $this->plugin->playSound($player, $messages["sound"]);
        $this->plugin->sendActionBar($player, $auctionbar);

        if ($config->get("join_leave")["enabled"]) {
            $this->plugin->getServer()->broadcastMessage($joinMessage);
        }
    }
}
