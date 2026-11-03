<?php
/**
 * Title: Kompetisies-bladsy
 * Slug: ink-foundation/kompetisies
 * Categories: ink-foundation, page
 * Inserter: no
 * Description: Die Kompetisies-bladsy: die jaarlikse kompetisie, kategorieë, die gala en die eksterne gids (N4).
 *
 * Presentation only: the four server-rendered ink-core blocks emit their own bands
 * (`05-kompetisies` §1). The group has no padding: it takes them out of `main`'s
 * constrained layout.
 *
 * @package Ink\Foundation
 */

?>
<!-- wp:group {"align":"full","className":"ink-kompetisies","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull ink-kompetisies">
	<!-- wp:ink/kompetisies-hero /-->
	<!-- wp:ink/kompetisies-kategoriee /-->
	<!-- wp:ink/kompetisies-gala /-->
	<!-- wp:ink/kompetisies-gids /-->
</div>
<!-- /wp:group -->
