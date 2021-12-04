u(".comment-reply").on("click", function(e) {
    let inputbox  = u("#write textarea");
    let comment   = u(e.target).closest(".post");
    let authorId  = comment.data("owner-id");
    let authorNm  = u(".post-author > a > b", comment.first()).text().trim();
    let fromGroup = Boolean(comment.data("from-group"));
    let postId    = comment.data("post-id");
    console.log(postId)
    
    inputbox.text("[" + (fromGroup ? "club" : "id") + "" + authorId + "|" + authorNm + "], ");
    inputbox.trigger("focusin");
});
