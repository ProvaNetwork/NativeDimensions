<?php
declare(strict_types=1);
namespace jasonwynn10\NativeDimensions;

use jasonwynn10\NativeDimensions\block\EndPortal;
use jasonwynn10\NativeDimensions\block\Fire;
use jasonwynn10\NativeDimensions\block\Obsidian;
use jasonwynn10\NativeDimensions\block\Portal;
use jasonwynn10\NativeDimensions\event\DimensionListener;
use jasonwynn10\NativeDimensions\world\DimensionalWorldManager;
use pocketmine\block\BlockFactory;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use Webmozart\PathUtil\Path;

class Main extends PluginBase {
	/** @var Main */
	private static $instance;
	/** @var int[] $teleporting */
	protected static $teleporting = [];

	public static function getInstance() : Main {
		return self::$instance;
	}

	public function onLoad() : void {
		self::$instance = $this;

		$this->getLogger()->debug("Unloading Worlds");
		$server = $this->getServer();
		$oldManager = $server->getWorldManager();
		foreach($oldManager->getWorlds() as $world)
			$oldManager->unloadWorld($world, true);
		$this->getLogger()->debug("Worlds Successfully Unloaded");

		// replace default world manager with one that supports dimensions
		$ref = new \ReflectionClass($server);
		$prop = $ref->getProperty('worldManager');
		$prop->setAccessible(true);
		$prop->setValue($server, new DimensionalWorldManager($server, Path::join($server->getDataPath(), "worlds")));

		if($this->getServer()->getWorldManager() instanceof DimensionalWorldManager)
			$this->getLogger()->debug("WorldManager Successfully swapped");
	}

	public function onEnable() : void {
		new DimensionListener($this);
		$factory = BlockFactory::getInstance();
		$parser = StringToItemParser::getInstance();
		foreach([
			new EndPortal(),
			new Fire(),
			new Obsidian(),
			new Portal()
		] as $block) {
			$factory->register($block, true);
			$parser->override($block->getName(), fn(string $input) => $block->asItem());
		}
	}

	/**
	 * @return int[]
	 */
	public static function getTeleporting() : array {
		return self::$teleporting;
	}

	public static function addTeleportingId(int $id) : void {
		if(!in_array($id, self::$teleporting))
			self::$teleporting[] = $id;
	}

	public static function removeTeleportingId(int $id) : void {
		$key = array_search($id, self::$teleporting);
		if($key !== false)
			unset(self::$teleporting[$key]);
	}
}