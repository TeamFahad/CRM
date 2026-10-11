# ফেজ ১: কোম্পানির কর্মী যোগ করা ও কাজের অনুমতি — ২০২৬-১০-১১

## সহজ ভাষায় আমরা কী বানাচ্ছি?

NovaFlow একটি মূল সফটওয়্যার। ভবিষ্যতে একাধিক কোম্পানি এটি ব্যবহার করবে।
প্রতিটি কোম্পানির জন্য আলাদা ব্যবসার জায়গা বা workspace থাকবে। উদাহরণ: “রহিম ট্রেডার্স”
ও “করিম এজেন্সি” একই সফটওয়্যার ব্যবহার করলেও তাদের কর্মী ও লিডের তথ্য আলাদা থাকবে।
এটাই tenant isolation: এক কোম্পানির পরিচয় দিয়ে অন্য কোম্পানির তথ্য পাওয়া যাবে না।

Role মানে কে কোন কাজ করতে পারবেন। কোম্পানির মালিক কর্মীকে invitation দিয়ে যোগ করবেন
এবং অনুমতি ঠিক করবেন। একটি মানুষের account একাধিক ব্যবসার সদস্য হতে পারে;
একটিতে manager হলেও অন্যটিতে শুধু auditor হতে পারেন।

ভবিষ্যতের বাণিজ্যিক পরিকল্পনা দুটি:

- মাসিক subscription: আমাদের পরিচালিত সফটওয়্যারে কোম্পানির আলাদা workspace থাকবে।
- আলাদা installation/license: একটি কোম্পানির নিজস্ব server-এ একই মূল সফটওয়্যারের আলাদা instance চালানো যেতে পারে।

আলাদা installation মানেই source code-এর একচ্ছত্র মালিকানা বিক্রি নয়। License, source access,
hosting, updates, support ও data ownership-এর শর্ত আলাদা করে ঠিক করতে হবে।
Subscription collection, automated provisioning, branded domains ও separate-install delivery
এখনো বানানো হয়নি। বর্তমানে CRM ভিত্তি ও কর্মী ব্যবস্থাপনা তৈরি হচ্ছে; সম্পূর্ণ ERP নয়।

## এই ফেজে কী তৈরি হয়েছে?

1. মালিকের জন্য Team পাতা: কর্মীদের নাম, ইমেইল, role ও invitation history।
2. ইমেইল ও role দিয়ে invitation link তৈরি; ৭২ ঘণ্টা মেয়াদ।
3. নির্দিষ্ট ইমেইলের account দিয়ে login ও email verification করে invitation গ্রহণ।
4. একবার গ্রহণ করা invitation আবার ব্যবহার করা যায় না।
5. মালিক লিংক বাতিল করতে পারেন। একই ইমেইলে নতুন invitation দিলে পুরোনো pending লিংক বাতিল হয়।
6. মালিক non-owner কর্মীর role পরিবর্তন ও সদস্যপদ বাতিল করতে পারেন।
7. কর্মী বাদ দিলে এই কোম্পানিতে তার প্রবেশ বন্ধ হবে; global account বা ব্যবসার পুরোনো রেকর্ড মুছে যাবে না।
8. Invitation, গ্রহণ, role change ও removal-এর সংক্ষিপ্ত audit record রাখা হয়।
9. সদস্য/লিংকের তালিকা pagination ব্যবহার করে; একসঙ্গে সব কর্মীর তথ্য load হয় না।
10. টিম পাতা ব্যবসার ভাষা অনুযায়ী বাংলা/ইংরেজি লেখা দেখায়; পুরো app-এর localization এখনো অসম্পূর্ণ।

## বর্তমানে অনুমতিগুলো

| Role          | এই ফেজে কী করতে পারবেন                                                              |
| ------------- | ----------------------------------------------------------------------------------- |
| Owner / মালিক | লিড দেখা/যোগ/status বদল, business terminology প্রকাশ, কর্মী invitation/role/removal |
| Manager       | লিড দেখা/যোগ/status বদল                                                             |
| Agent         | লিড দেখা/যোগ/status বদল                                                             |
| Auditor       | লিড দেখা; পরিবর্তন নয়                                                               |

Manager ও Agent-এর বর্তমান CRM ক্ষমতা একই। ভবিষ্যতে দরকার অনুযায়ী আলাদা হবে।
Audit viewer বা employee-specific lead assignment এখনো নেই। তাই audit permission registry
থাকা মানেই audit দেখার screen প্রস্তুত নয়। একই ব্যবসার অনুমতিপ্রাপ্ত সদস্যরা বর্তমানে
ব্যবসাটির লিড দেখতে পারেন; “শুধু নিজের assigned lead” নিয়ম এখনো নেই।

## মালিক কীভাবে ব্যবহার করবেন?

1. Account খুলে login ও email verification করুন।
2. Dashboard থেকে ব্যবসা তৈরি করুন অথবা নিজের ব্যবসা খুলুন।
3. Team পাতায় গিয়ে কর্মীর ইমেইল ও role বেছে link তৈরি করুন।
4. সঙ্গে সঙ্গে সম্পূর্ণ link কপি করে নির্দিষ্ট কর্মীকে ব্যক্তিগতভাবে দিন।
5. পরে invitation list-এ pending/accepted/revoked অবস্থা দেখুন। মেয়াদও দেখানো হয়।
6. Role বদলাতে কর্মীর card-এ নতুন role নির্বাচন করে save করুন।
7. সদস্যপদ বাতিল করতে Remove-এর পর confirmation দিন।

এই ফেজে invitation স্বয়ংক্রিয়ভাবে ইমেইল হয় না। Link একবার দেখানো হয়; হারালে নতুন
invitation তৈরি করুন। Email verification-এর mail আলাদা authentication ব্যবস্থা;
local setup-এ সেই mail Laravel log-এ থাকে, বাস্তব mail delivery provider যুক্ত নয়।

## কর্মী কীভাবে ব্যবহার করবেন?

1. Invitation-এ যে ইমেইল দেওয়া হয়েছে, সেটি দিয়েই account খুলুন বা login করুন।
2. ইমেইল যাচাই করুন।
3. মালিকের পাঠানো মূল link আবার খুলুন—login/verification redirect-এ security অংশ হারাতে পারে।
4. ব্যবসার নাম ও role দেখে Accept invitation চাপুন। শুধু link খুললেই সদস্যপদ হয় না।
5. গ্রহণ সফল হলে ব্যবসার overview-তে যাবেন। পরে dashboard থেকে ব্যবসা খুলতে পারবেন।

ভুল ইমেইলের account হলে invitation ব্যবহার করা যাবে না। মেয়াদ শেষ/বাতিল/আগে গ্রহণ করা
লিংক হলে নতুন invitation চাইতে হবে। Refresh করে security অংশ হারালেও মূল link আবার খুলুন।

## কীভাবে নিরাপত্তা রাখা হয়েছে?

- Browser-এ button লুকানোর পাশাপাশি server-এ permission পরীক্ষা হয়।
- Request-এ অন্য কোম্পানির ID পাঠিয়ে সেই কোম্পানির সদস্য/লিংক পরিবর্তন করা যায় না।
- Manager বা Agent নিজেকে মালিক করতে পারেন না। Team পাতা শুধু মালিক পরিচালনা করেন।
- এই screen দিয়ে মালিক বানানো, মালিকের role কমানো বা মালিক বাদ দেওয়া যায় না।
- Invitation-এর গোপন token-এর hash database-এ রাখা হয়; মূল token invitation row-তে নয়।
- Token link-এর `#token=...` অংশে থাকে, server GET log-এর URL query-তে নয়।
- Link flash session-এ encrypted; page history props-এ সংরক্ষণ করা হয় না।
- Token acceptance POST-এ যায়; validation error-এ old input হিসেবে ফেরত দেওয়া হয় না।
- Token, contact details বা ইমেইল audit metadata-তে লেখা হয় না।
- লিংক গ্রহণ এবং membership তৈরি এক transaction: audit ব্যর্থ হলে সব পরিবর্তন বাতিল হয়।
- একই ব্যবসার team changes আগে একটি database row lock নেয়, যাতে conflicting change
  একসঙ্গে অর্ধেকভাবে না চলে। পুরোনো role দেখে save/remove করলে reload করতে বলা হয়।
- Team/join response-এ no-store/no-referrer headers এবং mutation rate limits যোগ হয়েছে।

এগুলো application-level সুরক্ষা; database RLS, penetration test বা production security
certification সম্পন্ন হয়েছে—এমন দাবি নয়। Token কখনো public GitHub, analytics বা support
ticket-এ দেবেন না; future request-body logging-এ token redact করতে হবে।

## পরীক্ষা ও সীমাবদ্ধতা

২৩টি team feature test যোগ হয়েছে: permission escalation, owner safeguard, tenant isolation,
token hash/একবার ব্যবহার/expiry/revoke/reissue, verified recipient, stale permission,
role conflict এবং audit ব্যর্থতায় rollback পরীক্ষা করে।

দুটি PostgreSQL integration test আলাদা database connection দিয়ে invitation acceptance এবং
role-change/removal-এর lock পরীক্ষা করে। স্থানীয় SQLite-এ এগুলো skipped; PostgreSQL CI-তে
চালানোর ব্যবস্থা আছে। সর্বশেষ প্রকৃত ফল [VERIFICATION.md](VERIFICATION.md)-এ রাখা হবে।

Browser end-to-end, mobile/accessibility review, বড় traffic-এর পরীক্ষা, backup restore,
real mail delivery ও production deployment এখনো যাচাই হয়নি।

## পরের ফেজে কী করব?

CRM-এর দৈনন্দিন কাজ:

1. লিডের আলাদা detail পাতা—সম্ভাব্য ক্রেতার তথ্য এক জায়গায় দেখা।
2. নাম/contact/source ইত্যাদি সম্পাদনা, permission ও stale-update সুরক্ষা।
3. যোগাযোগের activity timeline—কখন ফোন/মিটিং/নোট হয়েছে তার ইতিহাস।
4. লিডকে customer-এ রূপান্তর; ভুলবশত দুবার customer না তৈরি করা।
5. সব নতুন কাজের tenant isolation, permission, transaction/audit ও conversion tests।

তারপর industry package ও typed custom fields: দোকান, agency বা service business-এর জন্য
একই সফটওয়্যারকে নিরাপদ configuration দিয়ে সাজানো। Sales/inventory/accounting-এর আগে
টাকা ও ledger-এর correctness rules এবং reconciliation tests লাগবে।

## অন্য GPT-কে কোথা থেকে শুরু করতে বলবেন?

Repository-এর README.md, AGENTS.md, docs/ROADMAP.md ও docs/ARCHITECTURE.md পড়তে বলুন।
পূর্ণ ভবিষ্যৎ specification হলো [PRODUCT_BLUEPRINT_BN.md](PRODUCT_BLUEPRINT_BN.md)।
এই phase report ও verification report দেখিয়ে বলুন: “বর্তমান Laravel/Inertia stack বজায় রেখে
পরবর্তী unfinished CRM milestone করুন; existing security tests ভাঙবেন না।”
Repository link দেওয়া থাকলেই সব ChatGPT session স্বয়ংক্রিয়ভাবে repo পড়বে—এমন নিশ্চয়তা নেই;
প্রয়োজনে repository access সংযুক্ত করুন অথবা প্রয়োজনীয় Markdown files upload করুন।
