<?php

namespace Luthfi\SimpleWelcome\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use Luthfi\SimpleWelcome\Main;

class SetWorldCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("sw", "Set the teleport world and coordinates", "/sw setworld", []);
        $this->setPermission("simplewelcome.command.setworld");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game.");
            return false;
        }

        if (count($args) !== 1 || strtolower($args[0]) !== "setworld") {
            $sender->sendMessage(TextFormat::YELLOW . "Usage: /sw setworld");
            return false;
        }

        $position = $sender->getPosition();
        $world = $position->getWorld();

        $this->plugin->getConfig()->set("teleport", [
            "enabled" => true,
            "world" => $world->getFolderName(),
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ()
        ]);

        $this->plugin->getConfig()->save();
        $sender->sendMessage(TextFormat::GREEN . "Teleport location set to world '{$world->getFolderName()}' at coordinates ({$position->getX()}, {$position->getY()}, {$position->getZ()}).");

        return true;
    }
}
