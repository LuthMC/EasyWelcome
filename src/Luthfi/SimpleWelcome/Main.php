<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\TextPacket;

class Main extends PluginBase implements Listener {

    private $enabled;
    private $title;
    private $subtitle;
    private $auctionbar;
    private $sound;
    private $joinLeaveEnabled;
    private $joinMessage;
    private $leaveMessage;

    public function onEnable(): void {
        $this->saveDefaultConfig(); // Save the default config.yml if it doesn't exist
        $config = $this->getConfig();
        $this->enabled = $config->get("enabled", true);
        $messages = $config->get("messages");
        $this->title = $messages["title"];
        $this->subtitle = $messages["subtitle"];
        $this->auctionbar = $messages["auctionbar"];
        $this->sound = $messages["sound"];

        $joinLeaveConfig = $config->get("join_leave");
        $this->joinLeaveEnabled = $joinLeaveConfig["enabled"];
        $this->joinMessage = $joinLeaveConfig["join_message"];
        $this->leaveMessage = $joinLeaveConfig["leave_message"];

        $this->getLogger()->info("SimpleWelcome enabled!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void {
        $this->getLogger()->info("SimpleWelcome disabled!");
    }

    /**
     * Handle player join event
     *
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        if (!$this->enabled) {
            return;
        }

        $player = $event->getPlayer();
        $playerName = $player->getName();
        $playerPing = $player->getNetworkSession()->getPing();

        $title = str_replace(["{name}", "{ping}"], [$playerName, $playerPing], $this->title);
        $subtitle = str_replace(["{name}", "{ping}"], [$playerName, $playerPing], $this->subtitle);
        $auctionbar = str_replace(["{name}", "{ping}"], [$playerName, $playerPing], $this->auctionbar);
        $joinMessage = str_replace("{name}", $playerName, $this->joinMessage);

        $soundPacket = new PlaySoundPacket();
        $soundPacket->soundName = $this->sound;
        $soundPacket->x = $player->getPosition()->getX();
        $soundPacket->y = $player->getPosition()->getY();
        $soundPacket->z = $player->getPosition()->getZ();
        $soundPacket->volume = 1;
        $soundPacket->pitch = 1;
        $player->getNetworkSession()->sendDataPacket($soundPacket);

        $titlePacket = new SetTitlePacket();
        $titlePacket->type = SetTitlePacket::TYPE_SET_TITLE;
        $titlePacket->text = $title;
        $player->getNetworkSession()->sendDataPacket($titlePacket);

        $subtitlePacket = new SetTitlePacket();
        $subtitlePacket->type = SetTitlePacket::TYPE_SET_SUBTITLE;
        $subtitlePacket->text = $subtitle;
        $player->getNetworkSession()->sendDataPacket($subtitlePacket);

        $auctionbarPacket = new TextPacket();
        $auctionbarPacket->type = TextPacket::TYPE_TIP;
        $auctionbarPacket->message = $auctionbar;
        $player->getNetworkSession()->sendDataPacket($auctionbarPacket);

        if ($this->joinLeaveEnabled) {
            $this->getServer()->broadcastMessage($joinMessage);
        }
    }

    /**
     * Handle player quit event
     *
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        if (!$this->enabled) {
            return;
        }

        $player = $event->getPlayer();
        $playerName = $player->getName();

        $leaveMessage = str_replace("{name}", $playerName, $this->leaveMessage);

        if ($this->joinLeaveEnabled) {
            $this->getServer()->broadcastMessage($leaveMessage);
        }
    }
}
