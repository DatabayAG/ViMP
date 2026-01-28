let xvmpCopyButtonBlurTimer = 3000;
function xvmpFocusOutside() {
  $("body").click()
}

let copyButtonPermanentLink = text => {
  if (window.navigator.clipboard) {
    setTimeout(xvmpFocusOutside, xvmpCopyButtonBlurTimer);
    return window.navigator.clipboard.writeText(text);
  }
};

let copyButtonStreamingLink = text => {
  if (window.navigator.clipboard) {
    setTimeout(xvmpFocusOutside, xvmpCopyButtonBlurTimer);
    return window.navigator.clipboard.writeText(text);
  }
};
