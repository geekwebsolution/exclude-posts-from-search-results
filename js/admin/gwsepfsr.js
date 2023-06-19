jQuery(document).ready(function () {

    var input = document.querySelector('textarea[id=gwsepfsr_custom_word_tag]');
    tagify = new Tagify(input, {
        enforceWhitelist : false,
        delimiters       : null,
    });

});