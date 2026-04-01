<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\NeoWiki\Persistence\MediaWiki;

use ProfessionalWiki\NeoWiki\NeoWikiExtension;
use ProfessionalWiki\NeoWiki\Persistence\LayoutNameLookup;
use MediaWiki\Title\TitleValue;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;

class DatabaseLayoutNameLookup implements LayoutNameLookup {

	public function __construct(
		private readonly IDatabase $db,
	) {
	}

	/**
	 * @return TitleValue[]
	 */
	public function getLayoutNames( int $limit, int $offset = 0 ): array {
		$res = $this->db->select(
			'page',
			[ 'page_title' ],
			[ 'page_namespace' => NeoWikiExtension::NS_LAYOUT ],
			__METHOD__,
			[
				'ORDER BY' => 'page_id ASC',
				'LIMIT' => $limit,
				'OFFSET' => $offset,
			]
		);

		return $this->dbResultToTitleValueArray( $res );
	}

	public function getLayoutCount(): int {
		/** @var string $count */
		$count = $this->db->selectField(
			'page',
			'COUNT(*)',
			[ 'page_namespace' => NeoWikiExtension::NS_LAYOUT ],
			__METHOD__
		);

		return (int)$count;
	}

	/**
	 * @return TitleValue[]
	 */
	private function dbResultToTitleValueArray( IResultWrapper $result ): array {
		$titles = [];

		foreach ( $result as $row ) {
			$titles[] = new TitleValue( NeoWikiExtension::NS_LAYOUT, $row->page_title );
		}

		return $titles;
	}

}
