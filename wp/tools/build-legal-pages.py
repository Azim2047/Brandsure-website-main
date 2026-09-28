#!/usr/bin/env python3
"""
Generate the company/legal pages as native Gutenberg blocks.

Content rules honoured here:
  * only company details that already appear on the site are used
    (BrandSure Consultancy Pvt. Ltd., CIN U73100KA2025PTC199105,
     info@brandsure.in, +91 8884 446 668, Bengaluru, Karnataka, India);
  * no street address, GSTIN, regulatory licence or extra email is invented;
  * no commercial promise, guarantee or partnership percentage appears;
  * content starts at H2 — the H1 is the page title.

Run: python3 wp/tools/build-legal-pages.py
"""

import html
import pathlib

ROOT = pathlib.Path(__file__).resolve().parents[2]
OUT = ROOT / "wp" / "plugin" / "brandsure-services" / "content"

COMPANY = "BrandSure Consultancy Pvt. Ltd."
CIN = "U73100KA2025PTC199105"
EMAIL = "info@brandsure.in"
PHONE = "+91 8884 446 668"
PLACE = "Bengaluru, Karnataka, India"
SITE = "https://brandsure.in"
UPDATED = "2 September 2026"


def p(text):
    return ("<!-- wp:paragraph -->\n<p>{}</p>\n<!-- /wp:paragraph -->").format(text)


def h(level, text):
    return (
        '<!-- wp:heading {{"level":{lvl}}} -->\n'
        '<h{lvl} class="wp-block-heading">{txt}</h{lvl}>\n'
        "<!-- /wp:heading -->"
    ).format(lvl=level, txt=text)


def ul(items):
    lis = "\n".join(
        "<!-- wp:list-item -->\n<li>{}</li>\n<!-- /wp:list-item -->".format(i)
        for i in items
    )
    return "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n{}\n</ul>\n<!-- /wp:list -->".format(lis)


def page(blocks):
    """Wrap the document in one group so the .bprose typography applies."""
    inner = "\n\n".join(blocks)
    return (
        '<!-- wp:group {"className":"bprose","layout":{"type":"constrained"}} -->\n'
        '<div class="wp-block-group bprose">\n'
        + inner
        + "\n</div>\n<!-- /wp:group -->\n"
    )


CONTACT_BLOCK = [
    h(2, "How to contact us"),
    p(
        "Questions, requests or complaints about this policy, or about how your "
        "information is handled, can be sent to us using the details below."
    ),
    ul(
        [
            "<strong>Company</strong>: {}".format(COMPANY),
            "<strong>CIN</strong>: {}".format(CIN),
            "<strong>Email</strong>: <a href=\"mailto:{0}\">{0}</a>".format(EMAIL),
            "<strong>Phone</strong>: <a href=\"tel:+918884446668\">{}</a>".format(PHONE),
            "<strong>Location</strong>: {}".format(PLACE),
        ]
    ),
    p(
        "Please mark your message clearly so it reaches the right person — for "
        "example with “Privacy request” or “Grievance” in the subject line."
    ),
]

UPDATED_BLOCK = [
    p('<em>Last updated: {}</em>'.format(UPDATED)),
]


# --------------------------------------------------------------------------
# Privacy Policy
# --------------------------------------------------------------------------
privacy = page(
    UPDATED_BLOCK
    + [
        p(
            "This Privacy Policy explains how {company} (“BrandSure”, “we”, “us”) "
            "handles information collected through {site} and through the enquiry "
            "channels linked from it. BrandSure is a company registered in India "
            "(CIN {cin}) and works with furniture manufacturers and showroom-led "
            "furniture businesses.".format(company=COMPANY, site=SITE, cin=CIN)
        ),
        p(
            "We handle personal data in accordance with applicable Indian law, "
            "including the Information Technology Act, 2000 and the rules made "
            "under it, and the Digital Personal Data Protection Act, 2023 and the "
            "rules made under it, to the extent those provisions are in force and "
            "apply to us."
        ),
        h(2, "Information we collect"),
        p("The information we hold falls into a small number of categories."),
        h(3, "Information you give us"),
        ul(
            [
                "Your name, business name, phone number and email address when you "
                "make an enquiry.",
                "The city or location of your business, and details of what you "
                "manufacture or sell, where you choose to share them.",
                "The content of messages you send us, including messages sent over "
                "WhatsApp, email or a form on this website.",
                "Any business information you share with us voluntarily during a "
                "conversation or an engagement.",
            ]
        ),
        h(3, "Information collected automatically"),
        ul(
            [
                "Technical data such as your IP address, browser type, device type, "
                "operating system and screen size.",
                "How you reached the website, including the referring website, search "
                "terms where these are made available to us, and campaign parameters "
                "in the link you clicked.",
                "How you use the website — pages viewed, time on page, scrolling, "
                "clicks and similar interaction events.",
            ]
        ),
        p(
            "We do not ask for and do not knowingly collect payment card numbers, "
            "bank details, government identifiers or other sensitive personal data "
            "through this website."
        ),
        h(2, "How we use this information"),
        ul(
            [
                "To respond to your enquiry and to continue the conversation you "
                "started.",
                "To understand whether we may be able to help your business, and to "
                "prepare for a discussion with you.",
                "To operate, secure and improve the website.",
                "To measure which marketing activity brings relevant enquiries, and "
                "to plan future activity.",
                "To keep records required for our own business, tax and compliance "
                "purposes.",
            ]
        ),
        p(
            "We do not sell your personal data, and we do not share your enquiry "
            "details with other furniture businesses."
        ),
        h(2, "Analytics, advertising and tracking technologies"),
        p(
            "This website uses, or may use, third-party services that set cookies or "
            "use similar technologies. These typically include:"
        ),
        ul(
            [
                "<strong>Google Analytics</strong> — to understand how the website is "
                "used in aggregate.",
                "<strong>Google Ads</strong> — including conversion measurement and "
                "remarketing, so our advertising can be measured and shown to people "
                "who have previously visited the website.",
                "<strong>Meta advertising tools</strong> — the Meta pixel and related "
                "technologies used for Facebook and Instagram advertising, "
                "measurement and remarketing.",
                "<strong>Other measurement or session-analysis tools</strong> used "
                "from time to time to diagnose usability problems.",
            ]
        ),
        p(
            "These services are operated by the respective third parties, who process "
            "the data they collect under their own privacy terms. Where remarketing "
            "is used, you may see BrandSure advertising on other websites or "
            "platforms after visiting this site. You can usually limit this through "
            "your Google and Meta ad settings, and through your browser's cookie "
            "controls. Our "
            '<a href="/cookie-policy/">Cookie Policy</a> explains the categories in '
            "more detail."
        ),
        h(2, "WhatsApp and other messaging"),
        p(
            "Several buttons on this website open a WhatsApp conversation with a "
            "message already written into it. If you send that message, your phone "
            "number and the message content reach us through WhatsApp, and the "
            "exchange is also subject to WhatsApp's own terms and privacy policy. We "
            "keep such conversations for as long as we need them to deal with your "
            "enquiry and to maintain our business records."
        ),
        h(2, "Sharing and disclosure"),
        p("We may share information in a limited set of circumstances:"),
        ul(
            [
                "With service providers who operate parts of our infrastructure — for "
                "example website hosting, email, analytics and advertising platforms — "
                "who process data on our instructions.",
                "With our professional advisers where necessary.",
                "Where we are required to do so by law, or by a competent authority, "
                "court or regulator.",
                "To establish, exercise or defend legal claims, or to protect the "
                "rights and safety of any person.",
            ]
        ),
        p(
            "Some of these providers may process data outside India. Where that "
            "happens, we take reasonable steps to satisfy ourselves that the data is "
            "handled appropriately."
        ),
        h(2, "How long we keep information"),
        p(
            "We keep enquiry and conversation records for as long as needed to deal "
            "with the matter and for a reasonable period afterwards, and for as long "
            "as we are required to keep records for legal, tax or accounting "
            "purposes. Analytics data is retained according to the settings of the "
            "platform concerned."
        ),
        h(2, "Your choices and rights"),
        p(
            "Subject to applicable law, you may ask us to give you access to the "
            "personal data we hold about you, to correct it if it is inaccurate or "
            "incomplete, or to erase it where there is no continuing need or legal "
            "requirement for us to keep it. You may also withdraw a consent you "
            "previously gave, and you may ask us to stop sending you marketing "
            "messages."
        ),
        p(
            "To make a request, write to us using the contact details below. We may "
            "need to ask you for information to confirm your identity before we act "
            "on a request. If you are not satisfied with how we have handled a "
            "request or a complaint, you may escalate it to the relevant authority "
            "under applicable Indian law."
        ),
        h(2, "Security"),
        p(
            "We take reasonable technical and organisational measures to protect the "
            "information we hold. No website or method of transmission over the "
            "internet is completely secure, so we cannot describe any system as "
            "absolutely safe, and you share information with us on that "
            "understanding."
        ),
        h(2, "Children"),
        p(
            "This website is aimed at businesses and is not directed at children. We "
            "do not knowingly collect personal data of children through this website."
        ),
        h(2, "Links to other websites"),
        p(
            "This website links to third-party websites and platforms that we do not "
            "control. This policy does not apply to them, and we are not responsible "
            "for their content or their privacy practices."
        ),
        h(2, "Changes to this policy"),
        p(
            "We may update this policy from time to time. The version published on "
            "this page is the one that applies, and the date at the top shows when it "
            "was last changed."
        ),
    ]
    + CONTACT_BLOCK
)

# --------------------------------------------------------------------------
# Terms & Conditions
# --------------------------------------------------------------------------
terms = page(
    UPDATED_BLOCK
    + [
        p(
            "These Terms &amp; Conditions govern your use of {site}, operated by "
            "{company} (CIN {cin}). By using this website you accept these terms. If "
            "you do not accept them, please do not use the website.".format(
                site=SITE, company=COMPANY, cin=CIN
            )
        ),
        p(
            "<strong>These terms cover the website only.</strong> They are not a "
            "services agreement. Any work BrandSure carries out for a client is "
            "governed by the separate written terms agreed for that engagement, and "
            "those terms prevail over anything on this website."
        ),
        h(2, "About us"),
        ul(
            [
                "<strong>Company</strong>: {}".format(COMPANY),
                "<strong>CIN</strong>: {}".format(CIN),
                "<strong>Based in</strong>: {}".format(PLACE),
                "<strong>Contact</strong>: <a href=\"mailto:{0}\">{0}</a>".format(EMAIL),
            ]
        ),
        h(2, "Use of this website"),
        p("You agree to use this website lawfully and reasonably. In particular you agree not to:"),
        ul(
            [
                "use the website for any unlawful purpose, or in breach of these terms;",
                "attempt to gain unauthorised access to the website, its server or any "
                "connected system;",
                "introduce any virus, malicious code or other harmful material;",
                "interfere with the normal operation of the website, or place an "
                "unreasonable load on it;",
                "scrape, harvest or systematically extract content from the website "
                "without our written permission;",
                "reproduce, republish or redistribute our content except as permitted "
                "below.",
            ]
        ),
        h(2, "Intellectual property"),
        p(
            "The content of this website — including text, layout, design, graphics, "
            "logos and the BrandSure name — belongs to us or is used by us with "
            "permission, and is protected by applicable law. You may view the site "
            "and print or download extracts for your own internal, non-commercial "
            "reference. You may not otherwise use our content or branding without our "
            "prior written consent."
        ),
        h(2, "Enquiries and communications"),
        p(
            "Sending an enquiry through this website, by email, or through a WhatsApp "
            "link does not create a contract or a professional relationship between "
            "us. We are free to decide whether to respond and whether to take on any "
            "particular engagement. Nothing on this website is an offer capable of "
            "acceptance."
        ),
        h(2, "Information on this website"),
        p(
            "The website describes the kinds of work BrandSure does. Descriptions of "
            "services are general, and the scope of any actual engagement is agreed "
            "in writing beforehand. Information on the site is provided for general "
            "information only and does not constitute professional, legal, financial "
            "or tax advice. Please see our "
            '<a href="/disclaimer/">Website Disclaimer</a> as well.'
        ),
        h(2, "No guarantee of outcomes"),
        p(
            "Business results depend on many factors outside our control, including "
            "your products, pricing, market, team, production capacity and delivery. "
            "Nothing on this website is a promise, projection or guarantee of any "
            "particular commercial result."
        ),
        h(2, "Third-party links and platforms"),
        p(
            "This website links to third-party websites and platforms, including "
            "WhatsApp and social media. We do not control them and are not "
            "responsible for their content, availability or terms. A link is not an "
            "endorsement."
        ),
        h(2, "Availability"),
        p(
            "We aim to keep the website available, but we do not undertake that it "
            "will be uninterrupted or error-free. We may change, suspend or withdraw "
            "any part of the website at any time without notice."
        ),
        h(2, "Limitation of liability"),
        p(
            "To the extent permitted by law, we are not liable for any loss or damage "
            "arising from your use of, or inability to use, this website, or from "
            "reliance on any information on it. This includes indirect or "
            "consequential loss, and loss of profit, revenue, business or data. "
            "Nothing in these terms limits any liability that cannot be limited under "
            "applicable law."
        ),
        h(2, "Privacy and cookies"),
        p(
            "Your use of this website is also governed by our "
            '<a href="/privacy-policy/">Privacy Policy</a> and our '
            '<a href="/cookie-policy/">Cookie Policy</a>.'
        ),
        h(2, "Changes to these terms"),
        p(
            "We may update these terms from time to time. The version published here "
            "is the one that applies, and continued use of the website after a change "
            "means you accept the updated terms."
        ),
        h(2, "Governing law"),
        p(
            "These terms are governed by the laws of India, and the courts at "
            "Bengaluru, Karnataka have jurisdiction over any dispute arising from "
            "them or from your use of this website."
        ),
    ]
    + CONTACT_BLOCK
)

# --------------------------------------------------------------------------
# Cookie Policy
# --------------------------------------------------------------------------
cookies = page(
    UPDATED_BLOCK
    + [
        p(
            "This Cookie Policy explains how {site}, operated by {company}, uses "
            "cookies and similar technologies. It should be read together with our "
            '<a href="/privacy-policy/">Privacy Policy</a>.'.format(
                site=SITE, company=COMPANY
            )
        ),
        h(2, "What cookies are"),
        p(
            "A cookie is a small text file placed on your device when you visit a "
            "website. Cookies let a site remember your actions and preferences, and "
            "let its owner understand how the site is being used. Similar "
            "technologies — pixels, tags, local storage and software development kits "
            "— do comparable things, and we use “cookies” in this policy to cover all "
            "of them."
        ),
        h(2, "Categories of cookies used on this website"),
        h(3, "Strictly necessary"),
        p(
            "These are needed for the website to work — for example to keep the site "
            "secure, to balance load, or to remember a choice you have made about "
            "cookies. The website cannot be provided properly without them."
        ),
        h(3, "Analytics and performance"),
        p(
            "These help us understand how visitors use the website in aggregate — "
            "which pages are viewed, how long people stay, where they leave, and "
            "which links they click. We use this to fix problems and improve the "
            "site. Google Analytics is the main service used in this category."
        ),
        h(3, "Advertising and remarketing"),
        p(
            "These are set by advertising platforms so that campaigns can be measured "
            "and so that BrandSure advertising can be shown to people who have "
            "previously visited this website. They typically include cookies and "
            "pixels associated with Google Ads and with Meta advertising for Facebook "
            "and Instagram. These technologies may also be used to build audiences "
            "for future advertising."
        ),
        h(2, "Third parties that may set cookies"),
        ul(
            [
                "<strong>Google</strong> — Google Analytics, and Google Ads conversion "
                "measurement and remarketing.",
                "<strong>Meta Platforms</strong> — the Meta pixel and related tools for "
                "Facebook and Instagram advertising and measurement.",
                "<strong>Other measurement or session-analysis providers</strong> used "
                "from time to time to diagnose usability problems.",
                "<strong>Our hosting and security providers</strong> — for cookies in "
                "the strictly necessary category.",
            ]
        ),
        p(
            "These third parties set and read their own cookies under their own "
            "policies. We do not control the cookies they set, and the list above may "
            "change as the tools we use change."
        ),
        h(2, "WhatsApp links"),
        p(
            "Buttons on this website that open WhatsApp take you to a service operated "
            "by WhatsApp. Once you leave this website, WhatsApp's own cookie and "
            "privacy terms apply to that interaction."
        ),
        h(2, "How to control cookies"),
        ul(
            [
                "<strong>In your browser</strong> — every major browser lets you block "
                "or delete cookies, and block third-party cookies specifically. Look "
                "under Settings, then Privacy.",
                "<strong>Google advertising</strong> — you can adjust personalised "
                "advertising in your Google account ad settings.",
                "<strong>Meta advertising</strong> — you can adjust ad preferences in "
                "your Facebook or Instagram account settings.",
                "<strong>Device settings</strong> — mobile operating systems offer "
                "controls that limit ad tracking.",
            ]
        ),
        p(
            "Blocking cookies in the strictly necessary category may stop parts of the "
            "website from working correctly. Blocking analytics or advertising cookies "
            "does not stop you using the website."
        ),
        h(2, "Changes to this policy"),
        p(
            "We may update this policy as the tools we use change. The version "
            "published here is the one that applies, and the date at the top shows "
            "when it was last changed."
        ),
    ]
    + CONTACT_BLOCK
)

# --------------------------------------------------------------------------
# Website Disclaimer
# --------------------------------------------------------------------------
disclaimer = page(
    UPDATED_BLOCK
    + [
        p(
            "This disclaimer applies to {site}, operated by {company} (CIN {cin}), and "
            "should be read together with our "
            '<a href="/terms-and-conditions/">Terms &amp; Conditions</a>.'.format(
                site=SITE, company=COMPANY, cin=CIN
            )
        ),
        h(2, "General information only"),
        p(
            "The content of this website is provided for general information about "
            "BrandSure and the kinds of work it does. It is not professional advice, "
            "and it is not legal, financial, tax, accounting, employment or "
            "engineering advice. You should not act, or refrain from acting, on the "
            "basis of anything on this website without taking advice appropriate to "
            "your own situation."
        ),
        h(2, "No advisory relationship"),
        p(
            "Reading this website, or sending an enquiry through it, does not create "
            "an advisory or professional relationship between you and BrandSure. Any "
            "engagement begins only when it has been agreed in writing, and its scope "
            "is whatever that written agreement says."
        ),
        h(2, "No guarantee of results"),
        p(
            "Nothing on this website is a promise, forecast or guarantee of any "
            "commercial outcome. Business performance depends on factors that are "
            "specific to each company and largely outside our control, including the "
            "product range, pricing, cost structure, territory, showroom execution, "
            "team, production capacity, delivery quality and market conditions. Where "
            "the website describes what a type of work can address, that is a "
            "description of scope, not an assurance of a result."
        ),
        h(2, "Illustrative figures and examples"),
        p(
            "Any figure, range, timeline or example mentioned on this website is "
            "illustrative and is included to explain how something is approached. It "
            "is not a quotation, an offer, a projection for your business, or a "
            "commitment. Actual commercial terms are set out only in a written "
            "proposal or agreement."
        ),
        h(2, "Accuracy and currency"),
        p(
            "We take reasonable care with the content of this website, but we do not "
            "warrant that it is complete, accurate or up to date at any given moment. "
            "Content may be changed or removed without notice."
        ),
        h(2, "Third-party content and links"),
        p(
            "This website links to third-party websites and platforms that we do not "
            "control, and may refer to third-party products or services. We are not "
            "responsible for their content, accuracy, availability or practices, and a "
            "reference or link is not an endorsement."
        ),
        h(2, "Responsibilities that remain with you"),
        p(
            "Where BrandSure supports a client, certain responsibilities remain with "
            "the client's own business unless a written agreement expressly says "
            "otherwise. These include manufacturing, product quality, delivery and "
            "installation, employment of staff and all statutory obligations that go "
            "with it, workplace safety, and legal, tax and regulatory compliance."
        ),
        h(2, "Limitation of liability"),
        p(
            "To the extent permitted by law, BrandSure is not liable for any loss or "
            "damage arising from use of this website or reliance on its content, "
            "including indirect or consequential loss and loss of profit, revenue, "
            "business or data. Nothing here limits any liability that cannot be "
            "limited under applicable law."
        ),
        h(2, "Governing law"),
        p(
            "This disclaimer is governed by the laws of India, and the courts at "
            "Bengaluru, Karnataka have jurisdiction over any dispute arising from it."
        ),
    ]
    + CONTACT_BLOCK
)


DOCS = {
    "privacy-policy": privacy,
    "terms-and-conditions": terms,
    "cookie-policy": cookies,
    "disclaimer": disclaimer,
}


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for slug, body in DOCS.items():
        path = OUT / (slug + ".blocks.html")
        path.write_text(body, encoding="utf-8")
        words = len(
            " ".join(
                l for l in body.splitlines() if not l.strip().startswith("<!--")
            ).split()
        )
        print("{:24s} {:6d} bytes  ~{} words".format(slug, path.stat().st_size, words))


if __name__ == "__main__":
    main()
