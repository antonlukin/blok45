import replaceEmbeds from './packages/replaceEmbeds';
import handleReactions from './packages/handleReactions';

replaceEmbeds( document.querySelectorAll( '.embed' ) );
handleReactions( document.querySelectorAll( '.reactions' ) );
