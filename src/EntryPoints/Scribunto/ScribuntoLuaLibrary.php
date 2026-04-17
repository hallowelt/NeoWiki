<?php

declare( strict_types = 1 );

namespace ProfessionalWiki\NeoWiki\EntryPoints\Scribunto;

use InvalidArgumentException;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;
use ProfessionalWiki\NeoWiki\Application\SubjectResolver;
use ProfessionalWiki\NeoWiki\Domain\Schema\SchemaName;
use ProfessionalWiki\NeoWiki\NeoWikiExtension;

class ScribuntoLuaLibrary extends LibraryBase {

	private ?SubjectDataLookup $subjectDataLookup = null;
	private ?SchemaLuaSerializer $schemaLuaSerializer = null;

	private function getSubjectDataLookup(): SubjectDataLookup {
		if ( $this->subjectDataLookup === null ) {
			$extension = NeoWikiExtension::getInstance();

			$this->subjectDataLookup = new SubjectDataLookup(
				new SubjectResolver(
					$extension->newSubjectContentRepository(),
					$extension->getSubjectRepository(),
				),
			);
		}

		return $this->subjectDataLookup;
	}

	private function getSchemaLuaSerializer(): SchemaLuaSerializer {
		if ( $this->schemaLuaSerializer === null ) {
			$this->schemaLuaSerializer = new SchemaLuaSerializer();
		}
		return $this->schemaLuaSerializer;
	}

	public function register(): array {
		$lib = [
			'getValue' => [ $this, 'getValue' ],
			'getAll' => [ $this, 'getAll' ],
			'getMainSubject' => [ $this, 'getMainSubject' ],
			'getSubject' => [ $this, 'getSubject' ],
			'getChildSubjects' => [ $this, 'getChildSubjects' ],
			'getSchema' => [ $this, 'getSchema' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.neowiki.lua', $lib, []
		);
	}

	public function getValue( ?string $propertyName = null, ?array $options = null ): array {
		$this->checkType( 'mw.neowiki.getValue', 1, $propertyName, 'string' );

		if ( $options !== null && ( isset( $options['page'] ) || isset( $options['subject'] ) ) ) {
			$this->incrementExpensiveFunctionCount();
		}

		return $this->getSubjectDataLookup()->getValue( $this->getTitle(), $propertyName, $options );
	}

	public function getAll( ?string $propertyName = null, ?array $options = null ): array {
		$this->checkType( 'mw.neowiki.getAll', 1, $propertyName, 'string' );

		if ( $options !== null && ( isset( $options['page'] ) || isset( $options['subject'] ) ) ) {
			$this->incrementExpensiveFunctionCount();
		}

		return $this->getSubjectDataLookup()->getAll( $this->getTitle(), $propertyName, $options );
	}

	public function getMainSubject( ?string $pageName = null ): array {
		$this->checkTypeOptional( 'mw.neowiki.getMainSubject', 1, $pageName, 'string', null );

		if ( $pageName !== null ) {
			$this->incrementExpensiveFunctionCount();
		}

		return $this->getSubjectDataLookup()->getMainSubjectData( $this->getTitle(), $pageName );
	}

	public function getSubject( ?string $subjectId = null ): array {
		$this->checkType( 'mw.neowiki.getSubject', 1, $subjectId, 'string' );
		$this->incrementExpensiveFunctionCount();

		return $this->getSubjectDataLookup()->getSubjectData( $subjectId );
	}

	public function getChildSubjects( ?string $pageName = null ): array {
		$this->checkTypeOptional( 'mw.neowiki.getChildSubjects', 1, $pageName, 'string', null );

		if ( $pageName !== null ) {
			$this->incrementExpensiveFunctionCount();
		}

		return $this->getSubjectDataLookup()->getChildSubjectsData( $this->getTitle(), $pageName );
	}

	public function getSchema( ?string $schemaName = null ): array {
		$this->checkType( 'mw.neowiki.getSchema', 1, $schemaName, 'string' );
		$this->incrementExpensiveFunctionCount();

		try {
			$name = new SchemaName( $schemaName );
		} catch ( InvalidArgumentException ) {
			return [ null ];
		}

		$schema = NeoWikiExtension::getInstance()->getSchemaLookup()->getSchema( $name );

		if ( $schema === null ) {
			return [ null ];
		}

		return [ $this->getSchemaLuaSerializer()->toLuaTable( $schema ) ];
	}

}
