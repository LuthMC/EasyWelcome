<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\Server;

class SimpleWelcome {
    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function handlePlayerJoin(Player $player): void {
        $config = $this->plugin->getConfig();
        if (!$config->get("enabled", true)) {
            return;
        }

        $playerName = $player->getName();
        $playerPing = $player->getNetworkSession()->getPing();
        $x = round($player->getPosition()->getX());
        $y = round($player->getPosition()->getY());
        $z = round($player->getPosition()->getZ());
        $onlineCount = count(Server::getInstance()->getOnlinePlayers());
        $worldName = $player->getWorld()->getDisplayName();

        if ($config->getNested("teleport.enabled", false)) {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($config->getNested("teleport.world", "world"));
            if ($world !== null) {
                $position = new Position($config->getNested("teleport.x", 0), $config->getNested("teleport.y", 0), $config->getNested("teleport.z", 0), $world);
                $player->teleport($position);
                $x = round($position->getX());
                $y = round($position->getY());
                $z = round($position->getZ());
                $worldName = $world->getDisplayName();
            } else {
                $this->plugin->getLogger()->warning("World '{$config->getNested("teleport.world", "world")}' not found. Teleportation failed.");
            }
        }

        $tags = ["{name}", "{ping}", "{x}", "{y}", "{z}", "{online}", "{world_name}"];
        $values = [$playerName, $playerPing, $x, $y, $z, $onlineCount, $worldName];

        $title = str_replace($tags, $values, $config->getNested("messages.title", "Welcome {name}!"));
        $subtitle = str_replace($tags, $values, $config->getNested("messages.subtitle", ""));
        $auctionbar = str_replace($tags, $values, $config->getNested("messages.auctionbar", ""));
        $joinMessage = str_replace($tags, $values, $config->getNested("join_leave.join_message", ""));

        $this->plugin->sendTitle($player, $title, $subtitle);
        $this->plugin->playSound($player, $config->getNested("messages.sound", "note.bell"));
        $this->plugin->sendActionBar($player, $auctionbar);

        if ($config->getNested("join_leave.enabled", false)) {
            $this->plugin->getServer()->broadcastMessage($joinMessage);
        }
    }

    public function handlePlayerQuit(Player $player): void {
        $config = $this->plugin->getConfig();
        if (!$config->get("enabled", true)) {
            return;
        }

        $playerName = $player->getName();
        $onlineCount = count(Server::getInstance()->getOnlinePlayers()) - 1;

        $leaveMessage = str_replace(["{name}", "{online}"], [$playerName, $onlineCount], $config->getNested("join_leave.leave_message", ""));

        if ($config->getNested("join_leave.enabled", false)) {
            $this->plugin->getServer()->broadcastMessage($leaveMessage);
        }
    }
}
