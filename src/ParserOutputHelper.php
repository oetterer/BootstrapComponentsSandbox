<?php
/**
 * Contains the class augmenting the parser output.
 *
 * @copyright (C) 2018, Tobias Oetterer, Paderborn University
 * @license       https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 (or later)
 *
 * This file is part of the MediaWiki extension BootstrapComponents.
 * The BootstrapComponents extension is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The BootstrapComponents extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup       BootstrapComponents
 * @author        Tobias Oetterer
 */

namespace MediaWiki\Extension\BootstrapComponents;

/*
 * TODO: When dropping support for MW1.39, use these class imports:
use MediaWiki\Html\Html;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
*/

use Html;
use Message;
use Parser;
use ParserOutput;
use Title;


/**
 * Class ParserOutputHelper
 *
 * Performs all the adaptions on the ParserOutput
 *
 * @since 1.0
 */
class ParserOutputHelper {

	/**
	 * To make sure, we only add the tracking category once.
	 *
	 * @var bool $articleTracked
	 */
	private $articleTracked;

	/**
	 * To make sure, we only add the error tracking category once.
	 *
	 * @var bool $articleTrackedOnError
	 */
	private $articleTrackedOnError;

	/**
	 * @var Parser $parser
	 */
	private $parser;


	/**
	 * ParserOutputHelper constructor.
	 *
	 * Do not instantiate directly, but use {@see ApplicationFactory::getParserOutputHelper} instead.
	 *
	 * @param Parser $parser
	 *
	 * @see ApplicationFactory::getParserOutputHelper
	 */
	public function __construct( $parser ) {
		$this->articleTracked = false;
		$this->articleTrackedOnError = false;
		$this->parser = $parser;
	}

	/**
	 * Adds the error tracking category to the current page if not done already.
	 */
	public function addErrorTrackingCategory() {
		if ( $this->articleTrackedOnError ) {
			return;
		}
		$this->placeTrackingCategory( 'bootstrap-components-error-tracking-category' );
		$this->articleTrackedOnError = true;
	}

	/**
	 * Adds the supplied modules to the parser output.
	 *
	 * @param array $modulesToAdd
	 *
	 * @deprecated use \MediaWiki\Extension\BootstrapComponents\BootstrapComponentsService::registerComponentAsActive
	 */
	public function addModules( $modulesToAdd ) {
		$parserOutput = $this->getParser()->getOutput();
		if ( is_a( $parserOutput, ParserOutput::class ) ) {
			// Q: when do we expect \Parser->getOutput() no to be a \ParserOutput? A:During tests.
			$parserOutput->addModules( $modulesToAdd );
		}
	}

	/**
	 * Adds the tracking category to the current page if not done already.
	 */
	public function addTrackingCategory() {
		if ( $this->articleTracked ) {
			return;
		}
		$this->placeTrackingCategory( 'bootstrap-components-tracking-category' );
		$this->articleTracked = true;
	}

	/**
	 * Unless I find a solution for the integration test problem, I cannot use an instance of
	 * ParserOutputHelper in ImageModal to ascertain this. In integration tests, "we" use a
	 * different parser than the InternalParseBeforeLinks-Hook. At least, after I added
	 * Scribunto _unit_ tests. All messed up, I'm afraid. ImageModal better use global parser, and
	 * for the time being this method will be
	 * @deprecated
	 *
	 * @return bool|null
	 */
	public function areImageModalsSuppressed() {
		return $this->getParser()->getOutput()
			->getExtensionData( BootstrapComponents::EXTENSION_DATA_NO_IMAGE_MODAL );
	}

	/**
	 * Allows for html fragments for a given container id to be stored, so that it can be added to the page at a later time.
	 *
	 * @param string $id
	 * @param string $rawHtml
	 *
	 * @return ParserOutputHelper $this (fluid)
	 */
	public function injectLater( $id, $rawHtml ) {
		if ( empty( $this->getParser()->getOutput() ) ) {
			# fix issues with PageForms file upload (issue #20)
			return $this;
		}
		if ( !empty( $rawHtml ) ) {
			$deferredContent = $this->getParser()->getOutput()->getExtensionData( BootstrapComponents::EXTENSION_DATA_DEFERRED_CONTENT_KEY );
			if ( empty( $deferredContent ) ) {
				$deferredContent = [];
			}
			$deferredContent[$id] = $rawHtml;
			$this->getParser()->getOutput()->setExtensionData( BootstrapComponents::EXTENSION_DATA_DEFERRED_CONTENT_KEY, $deferredContent );
		}
		return $this;
	}

	/**
	 * Formats a text as error text, so it can be added to the output.
	 *
	 * @param string $errorMessageName
	 *
	 * @return string
	 */
	public function renderErrorMessage( string $errorMessageName ): string {
		if ( !$errorMessageName || !trim( $errorMessageName ) ) {
			return '';
		}
		$this->addErrorTrackingCategory();
		return Html::rawElement(
			'span',
			[ 'class' => 'error' ],
			(new Message( trim( $errorMessageName ) ))->inContentLanguage()->page(
				$this->getParser()->getPage()
			)->parse()
		);

	}

	/**
	 * @return Parser
	 */
	protected function getParser(): Parser {
		return $this->parser;
	}

	/**
	 * Adds current page to the indicated tracking category, if not done already.
	 *
	 * @param String $trackingCategoryMessageName name of the message, containing the tracking category
	 */
	private function placeTrackingCategory( string $trackingCategoryMessageName ): void {
		$categoryMessage = (new Message( $trackingCategoryMessageName ))->inContentLanguage();
		$parserOutput = $this->parser->getOutput();
		if ( !$categoryMessage->isDisabled() && is_a( $parserOutput, ParserOutput::class ) ) {
			// Q: when do we expect Parser->getOutput() no to be a ParserOutput? A:During tests.
			$cat = Title::makeTitleSafe( NS_CATEGORY, $categoryMessage->text() );
			if ( $cat ) {
				$sort = (string)$parserOutput->getPageProperty('defaultsort') ?? '';
				$parserOutput->addCategory( $cat->getDBkey(), $sort );
			} else {
				wfDebug( __METHOD__ . ": [[MediaWiki:{$trackingCategoryMessageName}]] is not a valid title!\n" );
			}
		}
	}
}
