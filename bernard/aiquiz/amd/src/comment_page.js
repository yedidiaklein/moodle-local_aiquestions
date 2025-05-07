define(['jquery'], function($) {
    return {
        init: function(attemptId, slot) {
            console.log('AIQuiz: comment_page.js loaded');
            $.ajax({
                url: M.cfg.wwwroot + '/local/aiquiz/ajax.php',
                method: 'GET',
                data: {
                    attemptid: attemptId,
                    slot: slot
                },
                success: function(response) {
                    console.log('AIQuiz: Ajax response received');
                    $('#manualgradingform').before(response);
                },
                error: function(xhr, status, error) {
                    console.error('AIQuiz: Ajax request failed', error);
                }
            });
        }
    };
});