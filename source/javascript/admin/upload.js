import xHttp from "../xHttp.js";

let maxbyte = 0;
let activeDir = null;

function status(text) {
    document.getElementById("status").innerText = text;
}

function setFileInputMime(file, type) {
    file.setAttribute("accept", "");
    if (type === "image" || type === "lineimage") {
        file.setAttribute("accept", "image/*");
    } else if (type === "video") {
        file.setAttribute("accept", "video/*");
    }
}

function getFileSelectStatusMessage(type) {
    switch (type) {
        case "":
            return "Vælg den fil du vil sende";
        case "image":
        case "lineimage":
            return "Vælg de billeder du vil sende";
        case "video":
            return "Vælg den film du vil sende";
    }

    return "Vælg en filtype";
}

function showFileType() {
    const type = document.getElementById("type").value;
    const description = document.getElementById("description");
    const file = document.getElementById("file");

    description.style.display = (type === "image" || type === "lineimage") ? "" : "none";

    setFileInputMime(file, type);
    status(getFileSelectStatusMessage(type));

    if (type === "") {
        file.disabled = true;
        return;
    }
    file.disabled = false;
}

function fileExistsResponse(data) {
    if (data.exists) {
        alert("Bemærk en fil med navnet \"" + data.name +
              "\" allerede eksistere, og vil blive overskrevet hvis du fortsætter med denne upload!");
    }
}

function validate() {
    const files = document.getElementById("file").files;
    const submit = document.getElementById("submit");

    submit.disabled = true;
    if (!files.length) {
        return false;
    }

    for (const file of files) {
        if (file.size > maxbyte) {
            alert("Filen må max være på " + Math.round(maxbyte / 1024 / 1024 * 10) / 10 + "MB");
            return false;
        }

        status("Søger efter dubletter...");

        const path = activeDir + "/" + file.name;
        const type = document.getElementById("type").value;
        xHttp.request(
            "/admin/explorer/files/exists/?path=" + encodeURIComponent(path) + "&type=" + encodeURIComponent(type),
            fileExistsResponse);
    }
    status("");

    submit.disabled = false;
    return true;
}

function uploadCompleated() {
    status("Filerne er sendt");
    if (window.opener) {
        window.opener.showfiles(window.opener.activeDir);
    }
    document.getElementById("progress").style.display = "none";
    document.getElementById("type").disabled = false;
    document.getElementById("type").value = "";
    document.getElementById("file").value = "";
    document.getElementById("alt").value = "";
    validate();
}

let totals = [];
let uploads = [];
function updateProgress() {
    let totalSize = 0;
    for (const total of totals) {
        totalSize += total; // Set max before starting any uploads
    }

    let uploaded = 0;
    for (const upload of uploads) {
        uploaded += upload;
    }

    const progressBar = document.getElementById("progress");
    progressBar.max = parseInt(totalSize);

    const diff = totals.filter(function(element, index) {
        return element === uploads[index];
    });

    let statusText = " beregner upload...";
    if (uploaded) {
        progressBar.value = parseInt(uploaded);
        statusText = " uploader...";
    }
    status(diff.length + "/" + totals.length + statusText);

    if (progressBar.max === progressBar.value) {
        uploadCompleated();
    }
}

function getOnLoadFunction(x, i) {
    return function(data) {
        uploads[i] = totals[i];
        updateProgress();
        let result;
        try {
            result = JSON.parse(x.responseText);
        } catch (err) {
            result = {"uploaded": (x.status === 200 ? 1 : 0), "error": {"message": "Error: " + x.responseText}};
        }
        if (!result.uploaded) {
            alert(result.error.message);
        }
    };
}

function getOnProgressFunction(i) {
    return function(evt) {
        if (evt.lengthComputable) {
            totals[i] = evt.total;
        }
        uploads[i] = evt.loaded;
        updateProgress();
    };
}

function send() {
    status("Klargøre data.");
    totals = [];
    uploads = [];

    document.getElementById("type").disabled = true;
    document.getElementById("file").disabled = true;
    document.getElementById("submit").disabled = true;
    document.getElementById("description").style.display = "none";
    document.getElementById("description").style.display = "none";

    const progress = document.getElementById("progress");
    progress.removeAttribute("value");
    progress.style.display = "block";

    const files = document.getElementById("file").files;
    const form = new FormData();
    form.append("type", document.getElementById("type").value);
    form.append("alt", document.getElementById("alt").value);
    form.append("dir", activeDir);

    let x;
    for (const file of files) {
        form.append("upload", file);
        try {
            x = new window.XMLHttpRequest();
            if (typeof x.readyState !== "number") {
                throw "Bady readyState";
            }
        } catch (e) {
            continue;
        }
        const index = totals.push(file.size);
        uploads.push(NaN);
        x.onload = getOnLoadFunction(x, index - 1);
        x.upload.onprogress = getOnProgressFunction(index - 1);
        x.open("POST", "/admin/explorer/files/", true);
        x.send(form);
    }

    return false;
}

window.addEventListener("DOMContentLoaded", function(event) {
    window.send = send;
    window.showFileType = showFileType;
    window.validate = validate;
    maxbyte = window.maxbyte;
    activeDir = window.activeDir || "/images";
});
