<?php
declare(strict_types=1);
namespace jasonwynn10\NativeDimensions\block;

use jasonwynn10\NativeDimensions\Main;
use jasonwynn10\NativeDimensions\world\DimensionalWorld;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockLegacyIds as Ids;
use pocketmine\block\Opaque;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;

class EndPortal extends Opaque{
	use FacesOppositePlacingPlayerTrait;
	use HorizontalFacingTrait;

	public function __construct(){
		parent::__construct(new BID(Ids::END_PORTAL, 0), "End Portal", BlockBreakInfo::indestructible());
	}

	public function getLightLevel() : int{
		return 15;
	}

	/**
	 * @return AxisAlignedBB[]
	 */
	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::UP, 1 / 4)];
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onEntityInside(Entity $entity): bool{
		if(in_array($entity->getId(), Main::getTeleporting()))
			return true;

		/** @var DimensionalWorld $world */
		$world = $entity->getPosition()->getWorld();

		if(Main::isPortalDisabled($world))
			return true;

		Main::addTeleportingId($entity->getId());
		if($world->getOverworld() === $world){
			$world->getEnd()->orderChunkPopulation(100 >> Chunk::COORD_BIT_SIZE, 0 >> Chunk::COORD_BIT_SIZE, null)->onCompletion(
				function(Chunk $chunk) use ($world, $entity){
					Main::makeEndSpawn($world->getEnd());
					Main::getInstance()->getLogger()->debug("Teleporting to The End");
					$entity->teleport(new Position(100, 50, 0, $world->getEnd()));
					if($entity instanceof Player)
						$entity->getNetworkSession()->sendDataPacket(ChangeDimensionPacket::create(DimensionIds::THE_END, $entity->getPosition(), false));
				},
				function() use ($entity) {
					Main::getInstance()->getLogger()->debug("Failed to generate End chunks");
					Main::removeTeleportingId($entity->getId());
				}
			);
		}else {
			$world->getOverworld()->orderChunkPopulation(100 >> Chunk::COORD_BIT_SIZE, 0 >> Chunk::COORD_BIT_SIZE, null)->onCompletion(
				function() use($world, $entity) {
					$world->getLogger()->debug("Teleporting to the Overworld");
					$entity->teleport($entity instanceof Player ? $entity->getSpawn() : $world->getOverworld()->getSpawnLocation());
					if($entity instanceof Player)
						$entity->getNetworkSession()->sendDataPacket(ChangeDimensionPacket::create(DimensionIds::OVERWORLD, $entity->getPosition(), false));
				},
				function() use ($entity) {
					Main::getInstance()->getLogger()->debug("Failed to generate Overworld chunks");
					Main::removeTeleportingId($entity->getId());
				}
			);
		}
		return true;
	}

}