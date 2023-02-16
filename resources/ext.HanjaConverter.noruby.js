
(function() {
    mw.loader.using(['mediawiki.util']).then(function() {
        const params = new URLSearchParams(location.search);
        if(params.has('noruby')) {
            const elements = document.getElementsByTagName('a');
            for(let element of elements) {
                if(!element.href) continue;
                const url = new URL(element.href);
                if(url.host != location.host) continue;
                url.searchParams.set('noruby', params.get('noruby'));
                element.href = url.href;
            }
        }
    })
})();