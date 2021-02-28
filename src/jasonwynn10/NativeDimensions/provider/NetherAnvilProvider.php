<?php
declare(strict_types=1);
namespace jasonwynn10\NativeDimensions\provider;

class NetherAnvilProvider extends SubAnvilProvider {

	protected function pathToRegion(int $regionX, int $regionZ) : string{
		return $this->path . "/dim-1/region/r.$regionX.$regionZ." . static::REGION_FILE_EXTENSION;
	}

	public static function isValid(string $path) : bool{
		$isValid = (file_exists($path . "/level.dat") and is_dir($path . "/dim-1/region/"));

		if($isValid){
			$files = array_filter(scandir($path . "/dim-1/region/", SCANDIR_SORT_NONE), function($file){
				return substr($file, strrpos($file, ".") + 1, 2) === "mc"; //region file
			});

			foreach($files as $f){
				if(substr($f, strrpos($f, ".") + 1) !== static::REGION_FILE_EXTENSION){
					$isValid = false;
					break;
				}
			}
		}

		return $isValid;
	}

	public function getName() : string {
		return parent::getName()." dim-1";
	}

	public static function getProviderName() : string{
		return "nether_dimension";
	}

	/**
	 * @return string
	 */
	public function getGenerator() : string {
		return "nether";
	}
}