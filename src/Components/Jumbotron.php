<?php
/**
 * Contains the component class for rendering a jumbotron.
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

namespace MediaWiki\Extension\BootstrapComponents\Components;

use MediaWiki\Extension\BootstrapComponents\AbstractComponent;
use \Html;

/**
 * Class Jumbotron
 *
 * Class for component 'jumbotron'
 *
 * @see   https://github.com/oetterer/BootstrapComponents/blob/master/docs/components.md#Jumbotron
 * @since 1.0
 */
class Jumbotron extends AbstractComponent {
	/**
	 * @inheritdoc
	 *
	 * @param string $input
	 */
	protected function placeMe( $input ) {
		list ( $class, $style ) = $this->processCss( 'jumbotron', [] );
		# @hack: the outer container is a workaround, to get all the necessary css if not inside a grid container
		# @fixme: used inside mw content, the width calculation for smaller screens is broken (as of Bootstrap 1.2.3)
		return Html::rawElement(
			'div',
			[
				'class' => 'container',
			],
			Html::rawElement(
				'div',
				[
					'class' => $this->arrayToString( $class, ' ' ),
					'style' => $this->arrayToString( $style, ';' ),
					'id'    => $this->getId(),
				],
				$input
			)
		);
	}
}
