jQuery(document).ready(function() {
    jQuery.each(JSINFO.plugin.jplayer, function() {
        new jPlayerPlaylist({
            'jPlayer': '#' + this.ids.JPLAYER,
            'cssSelectorAncestor': '#' + this.ids.WRAPPER
        }, this.audio, this.options);
    });
});