u(document).on("click", ".comment-reply", function(e) {
    let comment   = u(e.target).closest(".post");
    let authorId  = comment.data("owner-id");
    let commentId = comment.data("comment-id");
    let authorNm  = u(".post-author > a > b", comment.first()).text().trim();
    let fromGroup = comment.attr("data-from-group") === "true";
    let postId    = comment.data("post-id");
    let inputbox  = postId == null ? u("#write textarea") : u("#wall-post-input" + (postId || ""));
    let mention   = ("[" + (fromGroup ? "club" : "id") + authorId + "|" + authorNm + "], ");
    let attachments = u("#post-buttons" + (postId | ""));

    // Substitute pervious mention if present, prepend otherwise
    inputbox.nodes.forEach(node => {
        node.value = node.value.replace(/(^\[([A-Za-z0-9]+)\|([\p{L} 0-9@]+)\], |^)/u, mention);
    })
    inputbox.trigger("focusin");

    let attachReply = attachments.find('[name="reply_to_comment"]')
    attachReply.nodes[0].value = commentId
    
    attachments.find('.post-replyto').html(`
        <span>${tr('reply_to_comment')}</span>
        <div id='remove_reply_button'></div>
    `)

    attachments.find('.post-replyto #remove_reply_button').on('click', (e) => {
        attachments.find('.post-replyto').html('')
        attachments.find(`input[name='reply_to_comment']`).attr('value', '')
    })
});
