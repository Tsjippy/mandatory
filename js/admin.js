function showUserList(pageId, button) {
  document.querySelector(`#wrapper-${pageId}`).classList.toggle("hidden");
  if (button.textContent.includes("Show")) {
    button.textContent = button.textContent.replace("Show", "Hide");
  } else {
    button.textContent = button.textContent.replace("Hide", "Show");
  }
}
