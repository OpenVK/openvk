window.addEventListener("scroll", function (e) {
  if (window.scrollY < 100) {
    if (window.temp_y_scroll) {
      u(".toTop").addClass("has_down");
      u(".toTop").removeClass("hidden");
    }

    document.body.classList.toggle("scrolled", false);
    u(".toTop").addClass("hidden");
  } else {
    document.body.classList.toggle("scrolled", true);
    u(".toTop").removeClass("has_down");
    u(".toTop").removeClass("hidden");
  }
});

u(".toTop").on("click", function (e) {
  const y_scroll = window.scrollY;
  const scroll_margin = 20;

  if (y_scroll > 100) {
    window.temp_y_scroll = y_scroll;
    window.scrollTo(0, scroll_margin);
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  } else {
    if (window.temp_y_scroll) {
      window.scrollTo(0, window.temp_y_scroll - scroll_margin);
      window.scrollTo({
        top: window.temp_y_scroll,
        behavior: "smooth",
      });
    }
  }

  u(document).trigger("scroll");
});
