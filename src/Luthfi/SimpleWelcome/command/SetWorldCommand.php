<?php

namespace Luthfi\SimpleWelcome\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Luthfi\SimpleWelcome\Main;

class SetWorldCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("sw", "Set the world and coordinates for teleportation");
        $this->setPermission("simplewelcome.setworld");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return false;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        $worldName = $sender->getWorld()->getDisplayName();
        $x = $sender->getPosition()->getX();
        $y = $sender->getPosition()->getY();
        $z = $sender->getPosition()->getZ();

        $this->plugin->getConfig()->set("teleport.world", $worldName);
        $this->plugin->getConfig()->set("teleport.x", $x);
        $this->plugin->getConfig()->set("teleport.y", $y);
        $this->plugin->getConfig()->set("teleport.z", $z);
        $this->plugin->getConfig()->save();

        $sender->sendMessage("Teleport world and coordinates set to: $worldName ($x, $y, $z)");

        return true;
    }
}
