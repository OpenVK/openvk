u(".comment-reply").on("click", function(e) {
    let comment   = u(e.target).closest(".post");
    let authorId  = comment.data("owner-id");
    let authorNm  = u(".post-author > a > b", comment.first()).text().trim();
    let fromGroup = Boolean(comment.data("from-group"));
    let postId    = comment.data("post-id");
    let inputbox  = postId == null ? u("#write textarea") : u("#wall-post-input" + (postId || ""));
    
    inputbox.text("[" + (fromGroup ? "club" : "id") + authorId + "|" + authorNm + "], ");
    inputbox.trigger("focusin");
});
