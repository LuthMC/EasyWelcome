<?php

# Github: https://github.com/LuthMC

namespace Luthfi\SimpleWelcome;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use Luthfi\SimpleWelcome\command\SetWorldCommand;

class Main extends PluginBase implements Listener {

    private $simpleWelcome;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $config = $this->getConfig();

        $this->simpleWelcome = new SimpleWelcome($this);

        $asciiArt = <<<EOT
 _____ _                 _       _    _      _                          
/  ___(_)               | |     | |  | |    | |                         
\ `--. _ _ __ ___  _ __ | | ___ | |  | | ___| | ___ ___  _ __ ___   ___ 
 `--. \ | '_ ` _ \| '_ \| |/ _ \| |/\| |/ _ \ |/ __/ _ \| '_ ` _ \ / _ \
/\__/ / | | | | | | |_) | |  __/\  /\  /  __/ | (_| (_) | | | | | |  __/
\____/|_|_| |_| |_| .__/|_|\___| \/  \/ \___|_|\___\___/|_| |_| |_|\___|
                  | |                                                   
                  |_|                                                   
EOT;

        $this->getLogger()->info($asciiArt);
        $this->getLogger()->info("SimpleWelcome Enabled!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("sw", new SetWorldCommand($this));
    }

    public function onDisable(): void {
        $this->getLogger()->info("SimpleWelcome Disabled!");
    }

    /**
     * Handle player join event
     *
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->simpleWelcome->handlePlayerJoin($player);

        $config = $this->getConfig();
        $effectsConfig = $config->get("effects");
        foreach ($effectsConfig as $effectName => $duration) {
            $effect = VanillaEffects::fromString($effectName);
            if ($effect !== null) {
                $player->getEffects()->add(new EffectInstance($effect, $duration * 20));
            }
        }
    }

    /**
     * Handle player quit event
     *
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $config = $this->getConfig();
        if (!$config->get("enabled", true)) {
            return;
        }

        $player = $event->getPlayer();
        $playerName = $player->getName();
        $onlineCount = count($this->getServer()->getOnlinePlayers()) - 1;

        $leaveMessage = str_replace(["{name}", "{online}"], [$playerName, $onlineCount], $config->get("leave_message"));

        if ($config->get("join_leave")["enabled"]) {
            $event->setQuitMessage("");
            $this->getServer()->broadcastMessage($leaveMessage);
        }
    }

    public function sendTitle(Player $player, string $title, string $subtitle): void {
        $titlePacket = new SetTitlePacket();
        $titlePacket->type = SetTitlePacket::TYPE_SET_TITLE;
        $titlePacket->text = $title;
        $player->getNetworkSession()->sendDataPacket($titlePacket);

        $subtitlePacket = new SetTitlePacket();
        $subtitlePacket->type = SetTitlePacket::TYPE_SET_SUBTITLE;
        $subtitlePacket->text = $subtitle;
        $player->getNetworkSession()->sendDataPacket($subtitlePacket);
    }

    public function playSound(Player $player, string $sound): void {
        $position = $player->getPosition();
        $soundPacket = new PlaySoundPacket();
        $soundPacket->soundName = $sound;
        $soundPacket->x = $position->getX();
        $soundPacket->y = $position->getY();
        $soundPacket->z = $position->getZ();
        $soundPacket->volume = 1;
        $soundPacket->pitch = 1;
        $player->getNetworkSession()->sendDataPacket($soundPacket);
    }

    public function sendActionBar(Player $player, string $message): void {
        $auctionbarPacket = new TextPacket();
        $auctionbarPacket->type = TextPacket::TYPE_TIP;
        $auctionbarPacket->message = $message;
        $player->getNetworkSession()->sendDataPacket($auctionbarPacket);
    }
}
