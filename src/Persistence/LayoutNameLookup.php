<?php

namespace ProfessionalWiki\NeoWiki\Persistence;

use MediaWiki\Title\TitleValue;

interface LayoutNameLookup {

	/**
	 * @return TitleValue[]
	 */
	public function getLayoutNames( int $limit, int $offset = 0 ): array;

	public function getLayoutCount(): int;

}
