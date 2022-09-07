OpenVK-KB-Heading: Editing notes

OpenVK wiki-markup is basically XHTML1.0 Transitional. The only difference is that we removed tags that are not needed or may harm OpenVK and it's users.

Allowed tags:
* All headers from level 3 to 6 (h3-h6)
* Paragraphs (&lt;p&gt;)
* Text formatting (&lt;i&gt;, &lt;b&gt;, &lt;del&gt;)
* &lt;sup&gt;, &lt;sub&gt;, &lt;ins&gt;
* Everything related to tables
* Links and images (&lt;a&gt;, &lt;img&gt;)
* Lists (and &lt;ol&gt; and &lt;ul&gt;)
* Line feed and horizontal rule (hr)
* Blockquotes (&lt;blockquote&gt; and &lt;cite&gt;)
* &lt;acronym&gt;

**Please note**: images can't have sourcemap and their source must be a file that is hosted on this OpenVK instance. This restrictions does not apply to links. Links can link to everything (except for data: and javascript: pseudoprotocols). They will be derefered though.

You may also have noticed, that &lt;style&gt; is note in the allowlist, however, we do support styling &lt;div&gt; and &lt;img&gt; tags using style attribute. This CSS properties are allowed:
* float
* height
* width
* max-height
* max-width
* font-weight

If property is a size property it can only accept pixels as value (no %, pt, pc, em, rem, vw or vh).
