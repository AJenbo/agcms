// TODO avoide overscaling when triming has been in affect

function saveImage(overwrite = false)
{
    $('save').style.display = 'none';
    $('loading').style.visibility = '';

    dimention = calcImageDimension();

    x_saveImage(path, cropX, cropY, maxW, maxH, dimention.width, dimention.height, flip, rotate, filename, overwrite,
        saveImage_r);
}

function calcImageDimension()
{
    var dimention = {};

    if(mode == "thb") {
        if(rotate) {
            dimention.width = Math.min(thumb_height, Math.round(maxW * scale));
            dimention.height = Math.min(thumb_width, Math.round(maxH * scale));
            return dimention;
        }

        dimention.width = Math.min(thumb_width, Math.round(maxW * scale));
        dimention.height = Math.min(thumb_height, Math.round(maxH * scale));
        return dimention;
    }

    dimention.width = Math.round(maxW * scale);
    dimention.height = Math.round(maxH * scale);

    return dimention;
}

function saveImage_r(data)
{
    $("loading").style.visibility = "hidden";
    $("save").style.display = "";
    if(data.error) {
        alert(data.error);
    } else if(data.yesno) {
        if(eval(confirm(data.yesno)) == true) {
            saveImage(true);
            return true;
        }

        self.close();
    } else if(window.opener.returnid && window.opener.returnid != "undefined") {
        window.opener.opener.document.getElementById(window.opener.returnid).value = data.id;
        window.opener.opener.document.getElementById(window.opener.returnid + "thb").src = data.path;
        // TODO make shure theas closes
        window.opener.close();
    } else if(window.opener.files[data.id]) {
        window.opener.files[data.id].width = data.width;
        window.opener.files[data.id].height = data.height;
        window.opener.files[data.id].refreshThumb();
    } else {
        window.opener.location.reload(true);
    }

    window.close();
    return true;
}

var CropImageManager = {
    // Holds the current Cropper.Img object
    curCrop : null,

    // Initialises the cropImageManager
    init : function() {},

    // Attaches/resets the image cropper
    attachCropper : function() {
        if(resizeHandle != null) {
            resizeEnd();
        }
        $('preview').style.display = 'none';
        $('original').style.display = '';
        $('resetCropper').style.display = 'none';
        $('removeCropper').style.display = '';
        $('preview').style.width = '';
        if(this.curCrop != null)
            this.curCrop.remove();
        this.curCrop = new Cropper.Img('original', {
            onEndCrop : onEndCrop,
            displayOnInit : true,
            onloadCoords : { x1 : cropX, y1 : cropY, x2 : maxW + cropX, y2 : maxH + cropY }
        });
    },
    // Removes the cropper
    removeCropper : function() {
        $('original').style.display = 'none';
        $('preview').style.display = '';
        $('removeCropper').style.display = 'none';
        $('resetCropper').style.display = '';
        preview();
        if(this.curCrop != null) {
            this.curCrop.remove();
        }
    },
    // Resets the cropper, either re-setting or re-applying
    resetCropper : function() {
        this.attachCropper();
    }
};

// setup the callback function
function onEndCrop(coords, dimensions)
{
    cropX = coords.x1;
    cropY = coords.y1;
    maxW = dimensions.width;
    maxH = dimensions.height;

    if(mode == 'thb') {
        if(rotate) {
            scale = Math.min(1, Math.max(thumb_height / maxW, thumb_width / maxH));
            return;
        }
        scale = Math.min(1, Math.max(thumb_width / maxW, thumb_height / maxH));
    }
}

// basic example
Event.observe(window, 'load', function test() {
    CropImageManager.init();
    Event.observe($('save'), 'click', CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($('flipV'), 'click', CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($('flipH'), 'click', CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($('cw'), 'click', CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe($('ccw'), 'click', CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe(
        $('removeCropper'), 'click', CropImageManager.removeCropper.bindAsEventListener(CropImageManager), false);
    Event.observe(
        $('resetCropper'), 'click', CropImageManager.resetCropper.bindAsEventListener(CropImageManager), false);
});
var resizeHandle = null;
function resize()
{
    $('save').style.display = '';
    $('loading').style.visibility = 'hidden';
    if(resizeHandle != null) {
        resizeHandle.destroy();
    }

    $('resizeHandle').style.left = $('preview').width + 'px';
    $('resizeHandle').style.top = $('preview').height + 'px';

    var maxWH = rotate ? maxH : maxW;

    if(mode == 'thb') {
        maxWH = maxWH * Math.min(1, Math.min(thumb_width / maxW, thumb_height / maxH));
        if(rotate) {
            maxWH = maxWH * Math.min(1, Math.min(thumb_width / maxH, thumb_height / maxW));
        }
    }

    resizeHandle = new Draggable('resizeHandle', {
        constraint : 'horizontal',
        boundary : [ [ 16, 0 ], [ maxWH, 0 ] ],
        onDrag : function(obj, e) {
            $('preview').style.width = obj.element.style.left;
            $('resizeHandle').style.top = $('preview').height + 'px';
            scale = (parseInt(obj.element.style.left) / (rotate ? maxH : maxW));
        },
        onEnd : function(e) {
            preview();
        }
    });
    $('resizeHandle').style.display = '';
}

function resizeEnd()
{
    resizeHandle.destroy();
    $('resizeHandle').style.display = 'none';
}

var cropX = 0;
var cropY = 0;
var position = 0; // 0-7
var rotate = 0;   // 90,180,270
var flip = 0;     // 1,2

function rotateCCW()
{
    switch(position) {
        case 0:
            position = 1;
            rotate = 90;
            flip = 0;
            break;
        case 1:
            position = 2;
            rotate = 180;
            flip = 0;
            break;
        case 2:
            position = 3;
            rotate = 270;
            flip = 0;
            break;
        case 3:
            position = 0;
            rotate = 0;
            flip = 0;
            break;
        case 4:
            position = 7;
            rotate = 90;
            flip = 1;
            break;
        case 5:
            position = 4;
            rotate = 0;
            flip = 1;
            break;
        case 6:
            position = 5;
            rotate = 270;
            flip = 1;
            break;
        case 7:
            position = 6;
            rotate = 0;
            flip = 2;
            break;
    }
    preview();
    $('preview').style.width = '';
}

function rotateCW()
{
    switch(position) {
        case 0:
            position = 3;
            rotate = 270;
            flip = 0;
            break;
        case 1:
            position = 0;
            rotate = 0;
            flip = 0;
            break;
        case 2:
            position = 1;
            rotate = 90;
            flip = 0;
            break;
        case 3:
            position = 2;
            rotate = 180;
            flip = 0;
            break;
        case 4:
            position = 5;
            rotate = 270;
            flip = 1;
            break;
        case 5:
            position = 6;
            rotate = 0;
            flip = 2;
            break;
        case 6:
            position = 7;
            rotate = 90;
            flip = 1;
            break;
        case 7:
            position = 4;
            rotate = 0;
            flip = 1;
            break;
    }
    preview();
    $('preview').style.width = '';
}

function flipHorizontal()
{
    switch(position) {
        case 0:
            position = 4;
            rotate = 0;
            flip = 1;
            break;
        case 1:
            position = 5;
            rotate = 270;
            flip = 1;
            break;
        case 2:
            position = 6;
            rotate = 0;
            flip = 2;
            break;
        case 3:
            position = 7;
            rotate = 90;
            flip = 1;
            break;
        case 4:
            position = 0;
            rotate = 0;
            flip = 0;
            break;
        case 5:
            position = 1;
            rotate = 90;
            flip = 0;
            break;
        case 6:
            position = 2;
            rotate = 180;
            flip = 0;
            break;
        case 7:
            position = 3;
            rotate = 270;
            flip = 0;
            break;
    }
    preview();
}

function flipVertical()
{
    switch(position) {
        case 0:
            position = 6;
            rotate = 0;
            flip = 2;
            break;
        case 1:
            position = 7;
            rotate = 90;
            flip = 1;
            break;
        case 2:
            position = 4;
            rotate = 0;
            flip = 1;
            break;
        case 3:
            position = 5;
            rotate = 270;
            flip = 1;
            break;
        case 4:
            position = 2;
            rotate = 180;
            flip = 0;
            break;
        case 5:
            position = 3;
            rotate = 270;
            flip = 0;
            break;
        case 6:
            position = 0;
            rotate = 0;
            flip = 0;
            break;
        case 7:
            position = 1;
            rotate = 90;
            flip = 0;
            break;
    }
    preview();
}

function preview()
{
    $('save').style.display = 'none';
    $('loading').style.visibility = '';

    dimention = calcImageDimension();

    $('preview').src = '/admin/explorer/image/?path=' + encodeURIComponent(path) + '&cropX=' + cropX + '&cropY=' + cropY
        + '&cropW=' + maxW + '&cropH=' + maxH + '&maxW=' + dimention.width + '&maxH=' + dimention.height + '&rotate='
        + rotate + '&flip=' + flip;
}
