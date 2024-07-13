<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\TextPacket;

class Main extends PluginBase implements Listener {

    private $enabled;
    private $title;
    private $subtitle;
    private $auctionbar;
    private $sound;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $config = $this->getConfig();
        $this->enabled = $config->get("enabled", true);
        $messages = $config->get("messages");
        $this->title = $messages["title"];
        $this->subtitle = $messages["subtitle"];
        $this->auctionbar = $messages["auctionbar"];
        $this->sound = $messages["sound"];

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
        $playerPing = $player->getPing();

        $title = str_replace(["{name}", "{ping}"], [$playerName, $playerPing], $this->title);
        $subtitle = str_replace(["{name}", "{ping}"], [$playerName, $playerPing], $this->subtitle);
        $auctionbar = str_replace(["{name}", "{ping}"], [$playerName, $playerPing], $this->auctionbar);

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
    }
}
