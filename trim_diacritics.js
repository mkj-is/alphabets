var nodiac = {
  // Čeština
  'á': 'a', 'č': 'c', 'ď': 'd', 'é': 'e', 'ě': 'e', 'í': 'i', 'ň': 'n', 'ó': 'o', 'ř': 'r', 'š': 's', 'ť': 't', 'ú': 'u', 'ů': 'u', 'ý': 'y', 'ž': 'z',
  // Slovenština
  'ä': 'a', 'ĺ': 'l', 'ľ': 'l', 'ô': 'o', 'ŕ': 'r',
  // Němčina
  'ö': 'o', 'ü': 'u', 'ß': 'ss',
  // Polština
  'ą': 'a', 'ę': 'e', 'ł': 'l', 'ć': 'c', 'ś': 's', 'ź': 'z', 'ż': 'z', 'ń': 'n',
  // идти ебать себя
  'Б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'ё': 'e', 'ж': 'z', 'з': 'z', 'и': 'i', 'й': 'i', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'y', 'ф': 'f', 'х': 'x', 'ц': 'o', 'ч': 'ts', 'ш': 'sh', 'щ': 'sht', 'ъ': '', 'ы': 'y', 'ь': 'i', 'э': 'je', 'ю': 'ju', 'я': 'ja', 'б': 'd'
};
/** Vytvoření přátelského URL
* @param string řetězec, ze kterého se má vytvořit URL
* @return string řetězec obsahující pouze čísla, znaky bez diakritiky, podtržítko a pomlčku
* @copyright Jakub Vrána, http://php.vrana.cz/
*/
function trim_diacritics(s) {
    s = s.toLowerCase();
    var s2 = '';
    for (var i=0; i < s.length; i++) {
        s2 += (typeof nodiac[s.charAt(i)] != 'undefined' ? nodiac[s.charAt(i)] : s.charAt(i));
    }
    return s2.replace(/[^a-z0-9_]+/g, '-').replace(/^-|-$/g, '');
}
