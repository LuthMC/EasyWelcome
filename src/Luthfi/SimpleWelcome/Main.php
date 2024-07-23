<?php

# Github: https://github.com/LuthMC
# Discord: LuthMC#5110

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
use Luthfi\SimpleWelcome\UpdateNotifier;
use pocketmine\scheduler\ClosureTask;
use DateTime;
use DateTimeZone;

class Main extends PluginBase implements Listener {

    private $enabled;
    private $title;
    private $subtitle;
    private $sound;
    private $auctionbar;
    private $joinLeaveEnabled;
    private $joinMessage;
    private $leaveMessage;
    private $teleportEnabled;
    private $teleportWorld;
    private $teleportX;
    private $teleportY;
    private $teleportZ;
    private $timezone;
    private $configVersion = "1.0.1";

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $config = $this->getConfig();
        $this->enabled = $config->get("enabled", true);
        $messages = $config->get("messages");
        $this->title = $messages["title"];
        $this->subtitle = $messages["subtitle"];
        $this->sound = $messages["sound"];
        $this->auctionbar = $messages["auctionbar"];

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

        $joinLeaveConfig = $config->get("join_leave");
        $this->joinLeaveEnabled = $joinLeaveConfig["enabled"];
        $this->joinMessage = $joinLeaveConfig["join_message"];
        $this->leaveMessage = $joinLeaveConfig["leave_message"];

        $teleportConfig = $config->get("teleport");
        $this->teleportEnabled = $teleportConfig["enabled"];
        $this->teleportWorld = $teleportConfig["world"];
        $this->teleportX = $teleportConfig["x"];
        $this->teleportY = $teleportConfig["y"];
        $this->teleportZ = $teleportConfig["z"];

        $this->timezone = $config->get("time")["timezone"];

        $this->getLogger()->info("SimpleWelcome Enabled!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $notifier = new UpdateNotifier($this, $this->configVersion);

        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($notifier): void {
            $notifier->checkForUpdates();
            $notifier->checkConfigVersion();
        }), 20 * 5);
    }

    public function onDisable(): void {
        $this->getLogger()->info("SimpleWelcome Disabled!");
    }

    private function getCurrentDateTime(): array {
        $dateTime = new DateTime("now", new DateTimeZone($this->timezone));
        return [
            "date" => $dateTime->format("Y-m-d"),
            "time" => $dateTime->format("H:i:s")
        ];
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
        $x = round($player->getPosition()->getX());
        $y = round($player->getPosition()->getY());
        $z = round($player->getPosition()->getZ());
        $onlineCount = count($this->getServer()->getOnlinePlayers());
        $worldName = $player->getWorld()->getDisplayName();
        
        $dateTime = $this->getCurrentDateTime();

        if ($this->teleportEnabled) {
            $world = $this->getServer()->getWorldManager()->getWorldByName($this->teleportWorld);
            if ($world !== null) {
                $position = new Position($this->teleportX, $this->teleportY, $this->teleportZ, $world);
                $player->teleport($position);
                $x = round($position->getX());
                $y = round($position->getY());
                $z = round($position->getZ());
                $worldName = $world->getDisplayName();
            } else {
                $this->getLogger()->warning("World '{$this->teleportWorld}' not found. Teleportation failed.");
            }
        }

        $serverIp = $this->getServer()->getIp();
        $serverPort = $this->getServer()->getPort();

        $tags = ["{name}", "{ping}", "{x}", "{y}", "{z}", "{online}", "{world_name}", "{date}", "{time}", "{ip}", "{port}"];
        $values = [$playerName, $playerPing, $x, $y, $z, $onlineCount, $worldName, $dateTime["date"], $dateTime["time"], $serverIp, $serverPort];
        $title = str_replace($tags, $values, $this->title);
        $subtitle = str_replace($tags, $values, $this->subtitle);
        $auctionbar = str_replace($tags, $values, $this->auctionbar);
        $joinMessage = str_replace($tags, $values, $this->joinMessage);

        $soundPacket = new PlaySoundPacket();
        $soundPacket->soundName = $this->sound;
        $soundPacket->x = $x;
        $soundPacket->y = $y;
        $soundPacket->z = $z;
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
            $event->setJoinMessage("");
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
        $onlineCount = count($this->getServer()->getOnlinePlayers()) - 1;

        $dateTime = $this->getCurrentDateTime();

        $leaveMessage = str_replace(
            ["{name}", "{online}", "{date}", "{time}", "{ip}", "{port}"],
            [$playerName, $onlineCount, $dateTime["date"], $dateTime["time"], $this->getServer()->getIp(), $this->getServer()->getPort()],
            $this->leaveMessage
        );

        if ($this->joinLeaveEnabled) {
            $event->setQuitMessage("");
            $this->getServer()->broadcastMessage($leaveMessage);
        }
    }
}
