<?php

namespace Spqr\Glossary\Plugin;

use Pagekit\Application as App;
use Pagekit\Content\Event\ContentEvent;
use Pagekit\Event\EventSubscriberInterface;
use Spqr\Glossary\Model\Item;
use Sunra\PhpSimple\HtmlDomParser;


class GlossaryPlugin implements EventSubscriberInterface
{
	/**
	 * Content plugins callback.
	 *
	 * @param ContentEvent $event
	 */
	public function onContentPlugins( ContentEvent $event )
	{
		$content = $event->getContent();
		$query   = Item::where( [ 'status = ?' ], [ Item::STATUS_PUBLISHED ] );
		$dom     = HtmlDomParser::str_get_html( $content );
		
		$node    = App::node();
		$config  = App::module( 'glossary' )->config();
		
		$target  = $config[ 'target' ];
		$tooltip = $config[ 'show_tooltip' ];
		
		if ( $node->link != "@glossary" ) {
			
			$markers = [];
			
			foreach ( $items = $query->get() as $key => $item ) {
				
				if ( $item->get( 'markdown' ) ) {
					$item->content = App::markdown()->parse( $item->content );
					$item->excerpt = App::markdown()->parse( $item->excerpt );
				}
				
				if ( empty( $item->excerpt ) ) {
					$item->excerpt = $item->content;
				}
				
				$url                                   = App::url( '@glossary/id', [ 'id' => $item->id ], 'base' );
				$markers[ strtolower( $item->title ) ] = [ 'text' => $item->title, 'url' => $url ];
				
				if ( is_array( $item->marker ) && !empty ( $item->marker ) ) {
					foreach ( $item->marker as $marker ) {
						$markers[ strtolower( $marker ) ] = [ 'text' => $marker, 'url' => $url ];
					}
				}
				
			}
			
			foreach ( $dom->find( 'text' ) as $element ) {
				if ( !in_array( $element->parent()->tag, [ 'a' ] ) ) {
					
					foreach ( $markers as $marker ) {
						$text = $marker[ 'text' ];
						$url  = $marker[ 'url' ];
						
						$excerpt = strip_tags( $item->excerpt );
						$tooltip = ( $tooltip ? "data-uk-tooltip title='$excerpt'" : "" );
						
						$element->innertext = preg_replace(
							'/\b' . preg_quote( $text, "/" ) . '\b/i',
							"<a href='$url' target='$target' $tooltip>\$0</a>",
							$element->innertext
						);
					}
				}
			}
			
			
			$event->setContent( $dom );
			
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function subscribe()
	{
		return [
			'content.plugins' => [ 'onContentPlugins', -1 ]
		];
	}
}