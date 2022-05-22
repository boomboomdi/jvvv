
var oTime = document.getElementById('time');
var sec = 0;
var sec_max = {$countdownTime};
var flag = false;
var expired = "/static/admin/ysf/img/qr_expire.jpg";
var url = location.search;

function download_app(object) {
    var isIOS = navigator.userAgent.match(/(iPhone|iPod|iPad);?/i);
    var r = confirm(`即将前往下载${object.innerText}APP，点确认继续？`);
    if (r) {
        if(isIOS) {
            window.location.href = object.getAttribute("data-ios");
        } else {
            window.location.href = object.getAttribute("data-android");
        }
    }
}

var obj = {};

var memo = ''; // Request("memo");
//支付金额
var amount = {$payableAmountShow};
//支付url
var orderUrl = {$orderUrl};
payUrl = decodeURIComponent(orderUrl);
//过期时间
var sec = {$countdownTime};

document.getElementById('dat').innerHTML = passDate();
//document.getElementById('mem').innerHTML = memo;
document.getElementById('amo').innerHTML = amount;
//document.getElementById('money').innerHTML = amount;
//document.getElementById('info').innerHTML = memo;
timerFunc();



//禁止鼠标右键、F12查看源码
document.oncontextmenu = function () { return false; };
document.onkeydown = function () {
    if (window.event && window.event.keyCode === 123) {
        event.keyCode = 0;
        event.returnValue = false;
        return false;
    }
};
//禁止下拉刷新
function stopReload(event) {
    event.preventDefault();
}
document.addEventListener('touchmove', stopReload, false);
//生成img节点 并绑定二维码
function createqr() {
    var typeNumber = 0;
    var errorCorrectionLevel = 'L';
    var qr = qrcode(typeNumber, errorCorrectionLevel);
    qr.addData(payUrl);
    qr.make();
    // img
    var img = document.createElement('img');
    img.setAttribute('src', qr.createDataURL());
    var canvas_obj = createCanvas(qr, 8, 10);
    var png_base64 = canvas_obj.toDataURL("image/png");
    var trade_create_time_str = amount;
    if (sec !== 0) {
        document.getElementById('qrcode_first').innerHTML = `<img src="${png_base64}" class="qr_image"/>`;
        document.getElementById('qrcode_first_link').setAttribute("href", png_base64);
    } else {
        document.getElementById('qrcode_first').innerHTML = `<img src="${expired}" class="qr_image"/>`;
        document.getElementById('qrcode_first_link').setAttribute("href", expired);
    }
}
function createCanvas(qr, cellSize = 2, margin = cellSize * 4) {
    var canvas = document.createElement('canvas');
    var size = qr.getModuleCount() * cellSize + margin * 2;
    canvas.width = size;
    canvas.height = size;
    var ctx = canvas.getContext('2d');

    // fill background
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // draw cells
    ctx.fillStyle = '#000';
    for (var row = 0; row < qr.getModuleCount(); row += 1) {
        for (var col = 0; col < qr.getModuleCount(); col += 1) {
            if (qr.isDark(row, col)) {
                ctx.fillRect(
                    col * cellSize + margin,
                    row * cellSize + margin,
                    cellSize, cellSize);
            }
        }
    }
    return canvas;
}
function date() {
    var time = new Date();
    var y = time.getFullYear();
    var mon = time.getMonth() + 1;
    mon = mon < 10 ? "0" + mon : mon;
    var d = time.getDate();
    d = d < 10 ? "0" + d : d;
    var h = time.getHours();
    h = h < 10 ? "0" + h : h;
    var m = time.getMinutes();
    m = m < 10 ? "0" + m : m;
    var s = time.getSeconds();
    s = s < 10 ? "0" + s : s;
    var sdate = y + "-" + mon + "-" + d + " " + h + ":" + m + ":" + s;
    return sdate;
}
function passDate() {
    var time = new Date(parseInt(new Date().getTime() + sec_max * 1000));
    var y = time.getFullYear();
    var mon = time.getMonth() + 1;
    mon = mon < 10 ? "0" + mon : mon;
    var d = time.getDate();
    d = d < 10 ? "0" + d : d;
    var h = time.getHours();
    h = h < 10 ? "0" + h : h;
    var m = time.getMinutes();
    m = m < 10 ? "0" + m : m;
    var s = time.getSeconds();
    s = s < 10 ? "0" + s : s;
    var sdate = mon + "-" + d + " " + h + ":" + m + ":" + s;
    return sdate;
}


// function onload() {
    createqr();
// }

function timerFunc() {
    if (flag) {
        return;
    }
    flag = true;
    var timer = setTimeout(() => {
        flag = false;
        timerFunc(sec);
    }, 1000);
    if (sec === 0) {
        clearTimeout(timer);
        oTime.innerHTML = "0 时 0 分 0 秒";
        document.getElementById('qrcode_first').innerHTML = `<img src="" class="qr_image"/>`;
        document.getElementById('qrcode_first_link').href = expired;
        document.getElementsByClassName('qr_image')[0].src = expired;
        document.getElementsByClassName('qr_image')[0].style.marginTop = "";
    } else {
        sec--;
        var m = parseInt(sec / 60);
        var s = parseInt(sec % 60);
        oTime.innerHTML = "0 时 " + m + " 分 " + s + " 秒";
    }
};
