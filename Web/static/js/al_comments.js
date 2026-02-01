u(document).on("click", ".comment-reply", function(e) {
    let comment   = u(e.target).closest(".post");
    let authorId  = comment.data("owner-id");
    let authorNm  = u(".post-author > a > b", comment.first()).text().trim();
    let fromGroup = Boolean(comment.data("from-group"));
    let postId    = comment.data("post-id");
    let inputbox  = postId == null ? u("#write textarea") : u("#wall-post-input" + (postId || ""));
    let mention   = ("[" + (fromGroup ? "club" : "id") + authorId + "|" + authorNm + "], ");
    
    // Substitute pervious mention if present, prepend otherwise
    inputbox.nodes.forEach(node => {
        node.value = node.value.replace(/(^\[([A-Za-z0-9]+)\|([A-Za-zА-Яа-яЁё0-9 @]+)\], |^)/, mention);
    })
    inputbox.trigger("focusin");
});
