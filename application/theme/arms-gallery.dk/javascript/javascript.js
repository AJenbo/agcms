function init()
{
    var links = document.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
        if (links[i].hostname !== location.hostname) {
            links[i].setAttribute('target', '_blank');
            links[i].setAttribute('title', 'Åbner i et nyt vindu');
        }
    }

    $('container').style.top = $('submenu').offsetTop + $('submenu').offsetHeight + 'px';
    var activmenu = $('activmenu');
    if(activmenu) {
        $('menu').scrollTop = activmenu.offsetTop;
    }
}
function $(id)
{
    return document.getElementById(id);
}

var krav;
function openkrav(url)
{
    var left = (screen.width - 512) / 2;
    var top = (screen.height - 395) / 2;
    krav = window.open(url, 'krav', 'toolbar=0,width=512,height=395,left = ' + left + ',top = ' + top);
    return false;
}

function armorsize()
{
    if($('armor_size').selectedIndex == 8) {
        $('armor_special').style.display = '';
        return;
    }

    $('armor_special').style.display = 'none';
}

function armorsex()
{
    if($('armor_sex').selectedIndex == 0) {
        $('armor_st4').disabled = true;
        $('armor_st4').className = 'gray';
        $('armor_st2').disabled = true;
        $('armor_st2').className = 'gray';
        $('armor_st5').disabled = true;
        $('armor_st5').className = 'gray';
        $('armor_st6').disabled = true;
        $('armor_st6').className = 'gray';
        $('armor_cupstr').disabled = true;
        $('armor_cupstr').className = 'gray';
        $('armor_bhstr').disabled = true;
        $('armor_bhstr').className = 'gray';
        $('armor_st8').disabled = true;
        $('armor_st8').className = 'gray';
        $('armor_st7').disabled = false;
        $('armor_st7').className = '';
        return;
    }

    $('armor_st4').disabled = false;
    $('armor_st4').className = '';
    $('armor_st2').disabled = false;
    $('armor_st2').className = '';
    $('armor_st5').disabled = false;
    $('armor_st5').className = '';
    $('armor_st6').disabled = false;
    $('armor_st6').className = '';
    $('armor_cupstr').disabled = false;
    $('armor_cupstr').className = '';
    $('armor_bhstr').disabled = false;
    $('armor_bhstr').className = '';
    $('armor_st8').disabled = false;
    $('armor_st8').className = '';
    $('armor_st7').disabled = true;
    $('armor_st7').className = 'gray';
}

function armorvalidate()
{
    if($('armor_navn').value == '') {
        alert('Du skal udfylde dit navn.');
        $('armor_navn').focus();
        return false;
    }
    if($('armor_adresse').value == '') {
        alert('Du skal udfylde dit adresse.');
        $('armor_adresse').focus();
        return false;
    }
    if($('armor_postnr').value == '') {
        alert('Du skal udfylde dit postnummer.');
        $('armor_postnr').focus();
        return false;
    }
    if($('armor_by').value == '') {
        alert('Du skal udfylde din by.');
        $('armor_by').focus();
        return false;
    }
    if($('armor_tlf1').value == '' && $('armor_tlf2').value == '' && $('armor_email').value == '') {
        alert('Du skal udfylde enten tlf. eller email.');
        $('armor_email').focus();
        return false;
    }

    if($('armor_size').selectedIndex == 8) {
        if($('armor_high').value == '') {
            alert('Du skal udfylde din højde.');
            $('armor_high').focus();
            return false;
        }
        if($('armor_kg').value == '') {
            alert('Du skal udfylde din vægt.');
            $('armor_kg').focus();
            return false;
        }
        if($('armor_sex').selectedIndex == 1) {
            // extra felter for kvinder
            if($('armor_st2').value == '') {
                alert('Du skal udfylde alle mål.');
                $('armor_st2').focus();
                return false;
            }
            if($('armor_cupstr').value == '') {
                alert('Du skal udfylde alle mål.');
                $('cupstr').focus();
                return false;
            }
            if($('armor_st4').value == '') {
                alert('Du skal udfylde alle mål.');
                $('armor_st4').focus();
                return false;
            }
            if($('armor_st5').value == '') {
                alert('Du skal udfylde alle mål.');
                $('armor_st5').focus();
                return false;
            }
            if($('armor_st6').value == '') {
                alert('Du skal udfylde alle mål.');
                $('armor_st6').focus();
                return false;
            }
            if($('armor_st8').value == '') {
                alert('Du skal udfylde alle mål.');
                $('armor_st8').focus();
                return false;
            }
            if($('armor_bhstr').value == '') {
                alert('Du skal udfylde alle mål.');
                $('bhstr').focus();
                return false;
            }
        } else {
            // extra felter for mænd
            if($('armor_st7').value == '') {
                alert('Du skal udfylde alle mål.');
                $('armor_st7').focus();
                return false;
            }
        }

        if($('armor_st1').value == '') {
            alert('Du skal udfylde alle mål.');
            $('armor_st1').focus();
            return false;
        }
        if($('armor_st3').value == '') {
            alert('Du skal udfylde alle mål.');
            $('armor_st3').focus();
            return false;
        }
        if($('armor_st9').value == '') {
            alert('Du skal udfylde alle mål.');
            $('armor_st9').focus();
            return false;
        }
        if($('armor_si7').value == '') {
            alert('Du skal udfylde alle mål.');
            $('armor_si7').focus();
            return false;
        }
        if($('armor_si8').value == '') {
            alert('Du skal udfylde alle mål.');
            $('armor_si8').focus();
            return false;
        }
        if($('armor_si9').value == '') {
            alert('Du skal udfylde alle mål.');
            $('armor_si9').focus();
            return false;
        }
    }
    return true;
}
