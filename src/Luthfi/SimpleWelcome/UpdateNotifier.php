<?php

namespace Luthfi\SimpleWelcome;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Internet;

class UpdateNotifier {

    private $plugin;
    private $configVersion;

    public function __construct(PluginBase $plugin, string $configVersion) {
        $this->plugin = $plugin;
        $this->configVersion = $configVersion;
    }

    public function checkForUpdates(): void {
        $url = "https://raw.githubusercontent.com/LuthMC/SimpleWelcome/main/plugin.yml";
        $response = Internet::getURL($url);
        if ($response !== null) {
            $remotePluginYml = yaml_parse($response);
            if (isset($remotePluginYml['version'])) {
                $remoteVersion = $remotePluginYml['version'];
                if (version_compare($this->plugin->getDescription()->getVersion(), $remoteVersion, '<')) {
                    $this->plugin->getLogger()->notice("A new version of SimpleWelcome is available: v$remoteVersion. Please update!");
                }
            }
        }
    }

    public function checkConfigVersion(): void {
        $config = $this->plugin->getConfig();
        if ($config->get("config_version") !== $this->configVersion) {
            $this->plugin->getLogger()->warning("The configuration file is outdated. Please update it to the latest version.");
        }
    }
}