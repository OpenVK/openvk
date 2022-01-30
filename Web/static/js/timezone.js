// This file is included only when there is no info about timezone in users's chandler session

xhr = new XMLHttpRequest();
xhr.open("POST", "/iapi/timezone", true);
xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
xhr.onload = () => {
    if(JSON.parse(response.originalTarget.responseText).response == 1) {
        window.location.reload();
    }
};
xhr.send('timezone=' + new Date().getTimezoneOffset());
