let copyButtonPermanentLink = text => {
  console.log(text);
  if (window.navigator.clipboard) {
    console.log(text);
    return window.navigator.clipboard.writeText(text);
  }
};

let copyButtonStreamingLink = text => {
  if (window.navigator.clipboard) {
    return window.navigator.clipboard.writeText(text);
  }
};
