jQuery(document).ready(function() {
    jQuery.each(JSINFO.plugin.jplayer, function() {
        console.log(this.ids, this.audio, this.options);
        new jPlayerPlaylist({
            'jPlayer': '#' + this.ids.JPLAYER,
            'cssSelectorAncestor': '#' + this.ids.WRAPPER
        }, this.audio, this.options);
    });
});