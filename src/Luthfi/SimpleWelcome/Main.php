<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

class Main extends PluginBase implements Listener {

    private $title;
    private $subtitle;
    private $sound;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $config = $this->getConfig()->get("messages");
        $this->title = $config["title"];
        $this->subtitle = $config["subtitle"];
        $this->sound = $config["sound"];

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
        $player = $event->getPlayer();

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
        $titlePacket->text = $this->title;
        $player->getNetworkSession()->sendDataPacket($titlePacket);

        $subtitlePacket = new SetTitlePacket();
        $subtitlePacket->type = SetTitlePacket::TYPE_SET_SUBTITLE;
        $subtitlePacket->text = $this->subtitle;
        $player->getNetworkSession()->sendDataPacket($subtitlePacket);
    }
}
