/**
 * Run Matomo instance.
 * This file will only be loaded if the PHP tracker properly authenticated.
 *
 * @since TBD
 */
( function() {
	var _paq = window._paq = window._paq || [];

	// Adds all custom dimensions passed through PHP. Must be before trackPageView.
	for ( const key in maiAnalyticsVars.dimensions ) {
		_paq.push( [ 'setCustomDimension', key, maiAnalyticsVars.dimensions[ key ] ] );
	}

	_paq.push( [ 'enableLinkTracking' ] );
	_paq.push( [ 'trackPageView' ] );
	_paq.push( [ 'trackVisibleContentImpressions' ] );
	// _paq.push( [ 'trackAllContentImpressions' ] );

	(function() {
		var u = maiAnalyticsVars.url;
		_paq.push( [ 'setTrackerUrl', u + 'matomo.php' ] );
		_paq.push( [ 'setSiteId', maiAnalyticsVars.siteID ] );
		var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
		g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
	})();
} )();