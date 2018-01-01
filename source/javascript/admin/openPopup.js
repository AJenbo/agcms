let popup = null;
function openPopup(url, windowName = "popup", width = 0, height = 0) {
    if (popup !== null) {
        popup.close();
        popup = null;
    }
    width = width || screen.availWidth;
    height = height || screen.availHeight;
    const left = (screen.availWidth - width) / 2;
    const top = (screen.availHeight - height) / 2;
    popup = window.open(url, windowName, "scrollbars=1,toolbar=0,width=" + width + ",height=" + height + ",left=" +
                                             left + ",top=" + top);
}

export default openPopup;
