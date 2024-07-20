<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use LootSpace369\lsform\SimpleForm;
use LootSpace369\lsform\CustomForm;
use LootSpace369\lsform\ModalForm;

class SimpleWelcomeCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("sw", "Open SimpleWelcome settings", "/sw");
        $this->setPermission("simplewelcome.command.sw");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }

        $this->sendMainForm($sender);
    }

    private function sendMainForm(Player $player) {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $this->sendEditForm($player, "title");
                    break;
                case 1:
                    $this->sendEditForm($player, "subtitle");
                    break;
                case 2:
                    $this->sendEditForm($player, "auctionbar");
                    break;
                case 3:
                    $this->sendEditForm($player, "sound");
                    break;
                case 4:
                    $this->sendEditForm($player, "joinMessage");
                    break;
                case 5:
                    $this->sendEditForm($player, "leaveMessage");
                    break;
                case 6:
                    $this->sendTeleportForm($player);
                    break;
            }
        });

        $form->setTitle("SimpleWelcome Settings");
        $form->addButton("Edit Title");
        $form->addButton("Edit Subtitle");
        $form->addButton("Edit Auctionbar");
        $form->addButton("Edit Sound");
        $form->addButton("Edit Join Message");
        $form->addButton("Edit Leave Message");
        $form->addButton("Edit Teleport Coordinates");

        $player->sendForm($form);
    }

    private function sendEditForm(Player $player, string $field) {
        $form = new CustomForm(function (Player $player, $data) use ($field) {
            if ($data === null) {
                return;
            }

            $config = $this->plugin->getConfig();
            $messages = $config->get("messages");

            $messages[$field] = $data[0];
            $config->set("messages", $messages);
            $config->save();

            $this->sendConfirmationForm($player, "Successfully updated {$field}!");
        });

        $form->setTitle("Edit " . ucfirst($field));
        $form->addInput("Enter new " . ucfirst($field), "", $this->plugin->getConfig()->get("messages")[$field]);

        $player->sendForm($form);
    }

    private function sendTeleportForm(Player $player) {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data === null) {
                return;
            }

            $config = $this->plugin->getConfig();
            $teleport = $config->get("teleport");

            $teleport["world"] = $data[0];
            $teleport["x"] = (int)$data[1];
            $teleport["y"] = (int)$data[2];
            $teleport["z"] = (int)$data[3];
            $config->set("teleport", $teleport);
            $config->save();

            $this->sendConfirmationForm($player, "Successfully updated teleport coordinates!");
        });

        $teleport = $this->plugin->getConfig()->get("teleport");

        $form->setTitle("Edit Teleport Coordinates");
        $form->addInput("World", "", $teleport["world"]);
        $form->addInput("X", "", (string)$teleport["x"]);
        $form->addInput("Y", "", (string)$teleport["y"]);
        $form->addInput("Z", "", (string)$teleport["z"]);

        $player->sendForm($form);
    }

    private function sendConfirmationForm(Player $player, string $message) {
        $form = new ModalForm(function (Player $player, $data) {
            if ($data === null) {
                return;
            }

            $this->sendMainForm($player);
        });

        $form->setTitle("Confirmation");
        $form->setContent($message);
        $form->setButton1("OK");
        $form->setButton2("Cancel");

        $player->sendForm($form);
    }
}
