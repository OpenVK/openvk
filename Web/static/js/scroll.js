window.addEventListener("scroll", function(e) {
    if(window.scrollY < 100) {
        document.body.classList.toggle("scrolled", false);
    } else {
        document.body.classList.toggle("scrolled", true);
    }
});

u(".toTop").on("click", function(e) {
    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
});