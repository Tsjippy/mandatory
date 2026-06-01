This module adds the possibility to make certain posts and pages mandatory.<br>
That means people have to mark the content as read.<br>
If they do not do so they will be reminded to read it until they do.<br>
A "I have read this" button will be automatically added to the e-mail if it is send by mailchimp.<br>
<br>
Adds one shortcode 'must_read_documents', which displays the pages to be read as links.<br>
Use like this <code>[must_read_documents]</code>.<br>

== Hooks ==
# FILTERS
- apply_filters('sim_mandatory_audience_param', $keys);
- apply_filters('sim_should_read_mandatory_page', $mustRead, $audience, $userId);
- sim-must-read
