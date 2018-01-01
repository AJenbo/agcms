import xHttp from "./xHttp.js";

let getAddressCall = null;
function getAddress(phonenumber, callback) {
    phonenumber = phonenumber.replace("/\\s/", "");
    phonenumber = phonenumber.replace("/^[+]45/", "");
    if (!phonenumber) {
        alert("De skal udfylde telefon nummeret først.");
        return false;
    }
    if (phonenumber.length !== 8) {
        alert("Telefonnummeret skal være på 8 cifre!");
        return false;
    }
    xHttp.cancel(getAddressCall);
    getAddressCall = xHttp.request("/ajax/address/" + phonenumber, callback);
    return false;
}

let arrayZipcode = {};

function changeZipCode(zipcode, countryId, cityId) {
    if (document.getElementById(countryId).value !== "DK") {
        return false;
    }
    if (arrayZipcode[zipcode]) {
        document.getElementById(cityId).value = arrayZipcode[zipcode];
    }
}

window.addEventListener("DOMContentLoaded", function(event) {
    xHttp.request("/javascript/zipcodedk.json", function(data) {
        arrayZipcode = data;
    });
});

export {getAddress, changeZipCode};
