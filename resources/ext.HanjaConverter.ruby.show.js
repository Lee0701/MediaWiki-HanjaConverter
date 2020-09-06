$.when(mw.loader.using( 'mediawiki.util' )).then(function() {
    var unknownLink = mw.user.options.values.displayRubyForUnknownLink == '1';
    var grade = mw.user.options.values.displayRubyForGrade;
    if(unknownLink) $('ruby.hanja.unknown > rt, ruby.hanja.unknown > rp').css('display', 'revert');
    var grades = [0, 10, 20, 30, 32, 40, 42, 50, 52, 60, 62, 70, 72, 80];
    if(grade) for(g of grades) {
        if('grade' + g == grade) break;
        $('ruby.hanja.grade' + g + ' > rt, ruby.hanja.grade' + g + ' > rp').css('display', 'revert');
    }
})