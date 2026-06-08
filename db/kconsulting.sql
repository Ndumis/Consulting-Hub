-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 08, 2026 at 08:14 AM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kconsulting`
--

-- --------------------------------------------------------

--
-- Table structure for table `bd_activities`
--

DROP TABLE IF EXISTS `bd_activities`;
CREATE TABLE IF NOT EXISTS `bd_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lead_id` int DEFAULT NULL,
  `activity_type` enum('call','email','meeting','follow_up','proposal') NOT NULL,
  `activity_date` datetime NOT NULL,
  `description` text,
  `outcome` text,
  `next_action` text,
  `next_action_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bd_activities`
--

INSERT INTO `bd_activities` (`id`, `lead_id`, `activity_type`, `activity_date`, `description`, `outcome`, `next_action`, `next_action_date`, `created_by`, `created_at`) VALUES
(1, 1, 'call', '2025-11-02 14:17:00', 'Meeting request', 'Meeting scheduled for next week to walk through the client our services', 'Walk through meeting', '2025-11-12', 1, '2025-11-02 12:19:10'),
(2, 1, 'meeting', '2025-11-02 12:41:00', 'Meeting request', 'Meeting scheduled for next week to walk through the client our services', 'Walk through meeting', '2025-11-12', 1, '2025-11-02 12:42:33');

-- --------------------------------------------------------

--
-- Table structure for table `bd_leads`
--

DROP TABLE IF EXISTS `bd_leads`;
CREATE TABLE IF NOT EXISTS `bd_leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `industry` varchar(50) DEFAULT NULL,
  `status` enum('new','contacted','meeting_booked','proposal_sent','client') DEFAULT 'new',
  `lead_score` int DEFAULT '0',
  `last_contact_date` date DEFAULT NULL,
  `next_follow_up` date DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bd_leads`
--

INSERT INTO `bd_leads` (`id`, `company_name`, `contact_person`, `email`, `phone`, `industry`, `status`, `lead_score`, `last_contact_date`, `next_follow_up`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Bima Concepts', 'enquiries', 'enquiries@bimaconcepts.co.za', '+27 66 089 9118', 'insurance', 'new', 0, '2025-11-02', NULL, '', 1, '2025-11-02 12:14:24', '2025-11-02 12:19:10');

-- --------------------------------------------------------

--
-- Table structure for table `bd_targets`
--

DROP TABLE IF EXISTS `bd_targets`;
CREATE TABLE IF NOT EXISTS `bd_targets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `month_year` date NOT NULL,
  `lead_target` int DEFAULT '0',
  `meeting_target` int DEFAULT '0',
  `client_target` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bd_targets`
--

INSERT INTO `bd_targets` (`id`, `month_year`, `lead_target`, `meeting_target`, `client_target`, `created_at`) VALUES
(1, '2025-11-01', 15, 5, 1, '2025-11-02 12:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `bd_tasks`
--

DROP TABLE IF EXISTS `bd_tasks`;
CREATE TABLE IF NOT EXISTS `bd_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_description` text NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `related_lead_id` int DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `related_lead_id` (`related_lead_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bd_tasks`
--

INSERT INTO `bd_tasks` (`id`, `task_description`, `due_date`, `assigned_to`, `status`, `related_lead_id`, `priority`, `created_by`, `created_at`) VALUES
(1, 'Call bima', '2025-11-02 14:16:00', 7, 'completed', 1, 'low', 1, '2025-11-02 12:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text,
  `content` longtext,
  `featured_image` varchar(500) DEFAULT NULL,
  `author` varchar(100) DEFAULT 'KConsulting Team',
  `category` varchar(100) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `read_time` int DEFAULT '5',
  `is_featured` tinyint(1) DEFAULT '0',
  `status` varchar(20) DEFAULT 'published',
  `views` int DEFAULT '0',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `author`, `category`, `tags`, `read_time`, `is_featured`, `status`, `views`, `published_at`, `created_at`) VALUES
(1, '5 Signs Your Website Is Losing You Leads', 'signs-your-website-is-losing-leads', 'Most business websites look professional but quietly turn away potential clients every day. Here are five warning signs — and what to do about each one.', 'Your website might be your biggest untapped asset — or your biggest liability. Most business owners assume that having a website means it is working for them. The truth is that the average business website converts less than 2% of its visitors into enquiries. That means 98 out of every 100 people who find you online leave without getting in touch.\n\nHere are five clear signs that your website is costing you leads, and what you can do about each one.\n\n**1. Your contact form is buried or complicated**\n\nIf someone has to click through three pages to find your contact form, you have already lost them. Visitors make decisions in seconds. Every extra step reduces the chance they will reach out. Your primary call-to-action should be visible above the fold on every key page, and your contact form should ask for the minimum required information — name, email, and message.\n\n**2. Your site loads slowly on mobile**\n\nOver 65% of web traffic in South Africa now comes from mobile devices. If your site takes more than three seconds to load on a phone, studies show that more than half of visitors will leave before it finishes loading. Google also ranks slower sites lower in search results, meaning fewer people find you in the first place. Use Google PageSpeed Insights to check your current score.\n\n**3. Your messaging is unclear within five seconds**\n\nWhen someone lands on your homepage, they are asking three questions: What do you do? Who do you do it for? Why should I choose you? If your homepage does not answer all three within the first few seconds, visitors leave confused. Clear, specific messaging outperforms clever every time.\n\n**4. You have no social proof above the fold**\n\nTestimonials, client logos, and results are some of the most powerful conversion tools on any website. If a visitor has to scroll to the bottom of your page to find evidence that you are credible and capable, most of them will not get that far. Place your strongest proof — a short quote, a result, or a client name — high on the page.\n\n**5. Your website has no follow-up mechanism**\n\nMost visitors are not ready to enquire on their first visit. If your only option is a contact form, you are losing everyone who is still in research mode. Adding a lead magnet, a free audit offer, or an email capture gives you a way to stay in touch with visitors who are interested but not ready yet.\n\nFixing even one of these issues can meaningfully improve the number of enquiries your website generates. If you would like a clear picture of how your site is performing, our free website audit covers all of these areas and more.', NULL, 'KConsulting Team', 'Marketing', 'Leads, Website, CRO, Conversion, UX', 5, 1, 'published', 274, '2026-05-01 09:00:00', '2026-06-08 08:58:53'),
(2, 'What Is Conversion Rate Optimisation and Why Does It Matter?', 'what-is-conversion-rate-optimisation', 'CRO is the science of turning more of your existing website visitors into customers — without spending more on advertising. Here is what you need to know.', 'Conversion Rate Optimisation, or CRO, is the process of improving your website so that a higher percentage of visitors take the action you want — whether that is submitting an enquiry form, booking a call, making a purchase, or downloading a resource.\n\nIf your website currently converts 1% of visitors and you improve that to 2%, you have doubled your leads without spending a single extra rand on advertising. That is the power of CRO.\n\n**Why most businesses ignore CRO**\n\nMost marketing budgets are spent on getting more traffic — through paid ads, SEO, or social media. Very little attention goes to what happens after the traffic arrives. The result is that businesses spend more and more to acquire visitors, while quietly losing the majority of them.\n\n**What CRO actually involves**\n\nGood CRO starts with understanding why visitors are not converting. This means reviewing heatmaps to see where people click and scroll, watching session recordings to understand user behaviour, analysing your funnel to see where people drop off, and testing changes to see which versions perform better.\n\nCommon improvements include simplifying contact forms, rewriting headlines to be more specific, adding trust signals like testimonials and certifications, improving page speed, and making call-to-action buttons more prominent and compelling.\n\n**What results can you expect?**\n\nCRO results vary depending on your starting point and the changes made, but it is common to see conversion rate improvements of 30% to 100% from a structured optimisation process. For a business with steady traffic, this can translate directly into a significant increase in monthly revenue without any increase in advertising spend.\n\n**CRO is not a one-time project**\n\nThe best results come from treating CRO as an ongoing process rather than a single project. Consumer behaviour changes, new competitors enter the market, and your own offerings evolve. Regular testing and refinement keeps your website performing at its best over time.', NULL, 'KConsulting Team', 'Marketing', 'CRO, Conversion, Growth, Website, Optimisation', 6, 0, 'published', 175, '2026-05-08 09:00:00', '2026-06-08 08:58:53'),
(3, 'How to Connect Your Business Tools with System Integration', 'how-to-connect-business-tools-system-integration', 'If your CRM, website, and communication tools do not talk to each other, you are losing time and leads every day. System integration is how you fix that.', 'Most growing businesses reach a point where their tools stop working together. Leads come in through a website form, but someone has to manually copy them into the CRM. Orders are placed in the online store, but the inventory system needs to be updated by hand. Customer service queries arrive in one inbox while the project team works in another.\n\nThis kind of manual data transfer is not just time-consuming — it is a source of errors, delays, and lost business. System integration solves this.\n\n**What is system integration?**\n\nSystem integration connects two or more software applications so they share data automatically. When a new lead submits your contact form, it appears in your CRM instantly, triggers a WhatsApp notification to your sales team, and sends an automated acknowledgement email to the client — all without anyone lifting a finger.\n\n**Common integrations for South African businesses**\n\nWebsite to CRM: Every enquiry or contact form submission flows directly into your client management system, tagged and assigned automatically.\n\nPayment gateway to accounting software: Payments reconcile automatically, removing hours of manual bookkeeping each week.\n\nWhatsApp to CRM: Customer conversations are logged against the correct client record so your team always has context.\n\nEcommerce to inventory: When a product sells, stock levels update across all channels immediately.\n\n**Tools we use for integration**\n\nMost integrations are built using APIs — the standardised communication layer that modern software applications expose. For businesses without technical resources, platforms like Zapier and n8n allow many integrations to be configured without writing code. For more complex requirements, we build custom integration layers.\n\n**The business impact**\n\nOur clients typically see a 30% to 60% reduction in manual admin time within the first month of implementing structured integrations. More importantly, no leads fall through the cracks, and the team can focus on work that actually grows the business.', NULL, 'KConsulting Team', 'Systems', 'API, Automation, CRM, Integration, Zapier, n8n', 7, 0, 'published', 178, '2026-05-15 09:00:00', '2026-06-08 08:58:53'),
(4, 'Cloud vs On-Premise: What Is Right for Your South African Business?', 'cloud-vs-on-premise-south-africa', 'Choosing between cloud and on-premise infrastructure is one of the most important IT decisions a growing business will make. Here is how to think about it.', 'The question of cloud versus on-premise infrastructure comes up for almost every business that starts to take its IT seriously. Both have legitimate advantages, and the right answer depends on your business size, budget, risk tolerance, and growth plans.\n\n**What is on-premise infrastructure?**\n\nOn-premise means your servers, storage, and networking equipment physically reside in your office or a dedicated server room. You own the hardware, manage the maintenance, and are responsible for uptime, backups, and security.\n\nThe advantage is control — you know exactly where your data is and who can access it. The disadvantage is cost. Hardware is expensive to buy and replace, maintenance requires either in-house expertise or an IT support contract, and scaling up means buying more equipment.\n\n**What is cloud infrastructure?**\n\nCloud infrastructure means your computing resources are hosted by a third-party provider — typically AWS, Microsoft Azure, or Google Cloud — and accessed over the internet. You pay for what you use, and scaling up or down can be done in minutes.\n\nFor most South African SMEs, cloud offers a compelling combination of lower upfront cost, built-in redundancy, and professional-grade security that would be prohibitively expensive to replicate on-premise.\n\n**Where South African businesses need to be careful**\n\nInternet reliability is a real consideration. If your business depends on cloud-hosted tools and your connectivity goes down, so does your operation. A hybrid approach — critical tools on cloud, with offline capabilities for essential functions — often works well here.\n\nData sovereignty is another factor. Certain industries have regulatory requirements about where data is stored. South Africa\'s POPIA legislation has implications for businesses storing personal information offshore.\n\n**Our recommendation for most SMEs**\n\nFor businesses with fewer than 50 staff, cloud is almost always the right choice. The cost savings, reliability, built-in backups, and scalability far outweigh the benefits of on-premise. For larger businesses or those with specific compliance requirements, a hybrid approach often makes the most sense.\n\nThe most important thing is not to let the decision be made by default — migrating away from an under-powered on-premise setup later is significantly more expensive and disruptive than starting right.', NULL, 'KConsulting Team', 'IT', 'Cloud, AWS, Infrastructure, South Africa, POPIA, On-Premise', 8, 0, 'published', 240, '2026-05-22 09:00:00', '2026-06-08 08:58:53'),
(5, 'The Complete Guide to Lead Generation for South African SMEs', 'lead-generation-guide-south-african-smes', 'Getting consistent, qualified leads is the number one challenge for most small and medium businesses. This guide covers everything you need to build a reliable lead generation system.', 'Lead generation is the process of attracting people who are likely to become customers and moving them toward making contact with your business. For most South African SMEs, this is the most pressing growth challenge they face.\n\nThe problem is not usually a lack of marketing activity. Most business owners are posting on social media, running occasional ads, and relying on word-of-mouth. The problem is the absence of a system — a reliable, repeatable process that generates enquiries consistently, regardless of who is doing what on a given week.\n\n**The three stages of lead generation**\n\nEvery effective lead generation system has three stages: attract, capture, and convert.\n\nAttracting means getting the right people to find you — through organic search, paid advertising, social media, referrals, or partnerships. The channel matters less than the targeting. You want people who have the problem your business solves.\n\nCapturing means giving those visitors a reason to share their contact details. This might be a contact form, a free audit offer, a downloadable guide, or a WhatsApp chat widget. Without a capture mechanism, most visitors will leave and never return.\n\nConverting means following up in a way that builds trust and moves people toward a buying decision. This is where most businesses fail. They capture a lead, send one email, and give up. A structured follow-up sequence — whether by email, WhatsApp, or phone — dramatically increases the percentage of leads that become clients.\n\n**Why most lead gen fails for South African SMEs**\n\nThree reasons account for most lead generation failures. First, the website is not optimised to convert visitors — unclear messaging, slow load times, or no visible call-to-action. Second, there is no follow-up system, so leads go cold. Third, there is no tracking, so the business does not know which channels or messages are actually working.\n\n**Building your lead generation system**\n\nStart with your website. Ensure that every key page has a clear, specific call-to-action. Reduce the friction in your contact form. Add at least one lead magnet — something valuable enough that a potential client would trade their email address for it.\n\nNext, set up a simple CRM. Even a basic system ensures that no lead is forgotten and that every person who expresses interest receives consistent follow-up.\n\nFinally, track everything. Know where your leads come from, which ones convert, and how long the average sales cycle takes. This data tells you where to invest more and where to stop wasting money.\n\nBuilding a working lead generation system takes time, but the compounding effect is significant. Businesses that do it well stop worrying about where their next client is coming from.', NULL, 'KConsulting Team', 'Growth', 'Leads, SME, Growth, Strategy, Funnel, CRM', 10, 1, 'published', 317, '2026-05-29 09:00:00', '2026-06-08 08:58:53'),
(6, 'Why Your Ecommerce Store Is Not Converting (And How to Fix It)', 'why-ecommerce-store-not-converting', 'Traffic is not your problem. Most ecommerce stores have enough visitors — they just fail to convert them into buyers. Here are the most common reasons, and the fixes.', 'Getting traffic to an ecommerce store is easier than it has ever been. Paid ads, social media, and SEO can all drive consistent visitors to your products. The problem that most store owners eventually run into is not traffic — it is conversion.\n\nThe average ecommerce conversion rate globally sits around 2% to 3%. South African stores often see even lower numbers, partly due to trust barriers around online payments. If your store is converting at below 1%, you are leaving significant revenue on the table every single month.\n\n**The checkout is killing your sales**\n\nThe single biggest cause of lost ecommerce revenue is a complicated checkout. Every extra field, every unexpected cost, every required account creation is a reason for a buyer to abandon their cart. Studies consistently show that cart abandonment rates drop significantly when checkout is simplified to the minimum required steps.\n\nFix: Audit your checkout. Remove every field that is not essential. Enable guest checkout. Show the full cost — including shipping — before the final step. Add trust badges near the payment button.\n\n**Your product pages are not doing enough work**\n\nA buyer who cannot quickly understand exactly what they are getting, why it is worth the price, and why they should trust you will not buy. Product pages need clear images from multiple angles, specific descriptions that address the buyer\'s question, visible pricing, and social proof — reviews, ratings, or buyer counts.\n\n**Mobile experience is an afterthought**\n\nIn South Africa, mobile commerce is dominant. If your product images are slow to load, your add-to-cart button is hard to tap, or your checkout is difficult to complete on a phone, you are losing the majority of your potential buyers.\n\n**Payment options are limited**\n\nLocal buyers want to pay the way they are comfortable paying. If you only offer one payment method and a buyer does not use it, the sale is lost. Offering PayFast, Ozow, credit card, and EFT covers most South African buyers.\n\n**There is no urgency or reason to buy now**\n\nMost visitors who do not buy immediately never return. Ethical urgency — limited stock indicators, limited-time offers, or shipping cutoff times — gives buyers who are on the fence a reason to act now rather than later.\n\nFixing these issues does not require a full rebuild of your store. Targeted, methodical changes — tested and measured — can double your conversion rate without increasing your advertising spend.', NULL, 'KConsulting Team', 'Marketing', 'eCommerce, CRO, Checkout, Revenue, WooCommerce, Conversion', 7, 0, 'published', 250, '2026-06-01 09:00:00', '2026-06-08 08:58:53'),
(7, 'GEO: How to Get Your Business Recommended by AI Tools Like ChatGPT', 'geo-get-business-recommended-by-ai', 'Traditional SEO gets you found on Google. Generative Engine Optimisation (GEO) gets your business recommended by ChatGPT, Google Gemini, and Microsoft Copilot.', 'Search behaviour is changing fast. A growing number of people now start their buying research not on Google, but by asking an AI assistant — ChatGPT, Google Gemini, or Microsoft Copilot. They type questions like \"what is the best web design company in Cape Town\" or \"which South African IT firm should I use for cloud migration\" and expect the AI to recommend specific businesses.\n\nIf your business is not set up to be found and recommended by these AI platforms, you are invisible to a growing segment of potential clients.\n\n**What is Generative Engine Optimisation?**\n\nGenerative Engine Optimisation (GEO) is the practice of structuring your online presence, content, and business information so that AI-powered platforms can understand what you do, who you serve, and why you are credible — and recommend you in response to relevant queries.\n\nIt is not the same as traditional SEO. SEO optimises for keyword rankings in search results. GEO optimises for the way large language models synthesise and recommend information.\n\n**How AI tools decide what to recommend**\n\nAI assistants draw on multiple sources: the web content they were trained on, real-time search results, structured business data, reviews, and authoritative third-party mentions. Businesses that appear consistently across credible, specific sources are more likely to be surfaced in AI responses.\n\n**What GEO involves in practice**\n\nClear, specific website content that directly answers questions your target clients are likely to ask. Generic marketing copy does not help AI tools understand what you do — specific, substantive answers do.\n\nStructured data markup on your website tells search engines and AI platforms exactly what your business is, who it serves, and what services it provides.\n\nConsistent NAP data (Name, Address, Phone) across Google Business Profile, directories, and your website ensures AI tools can verify and reference your business reliably.\n\nAuthority signals — being mentioned in industry publications, having genuine reviews, and earning links from credible sources — all contribute to the AI\'s confidence in recommending your business.\n\n**Is GEO replacing SEO?**\n\nNot yet, and possibly not entirely. Traditional search still drives significant traffic, and many buyers still use Google in the conventional way. The smart approach is to build a strategy that works for both — which, fortunately, has a great deal of overlap.', NULL, 'KConsulting Team', 'Marketing', 'GEO, AI, SEO, ChatGPT, Google Gemini, Digital Marketing', 6, 0, 'published', 154, '2026-06-04 09:00:00', '2026-06-08 08:58:53'),
(8, '5 Business Metrics Every South African SME Should Track Weekly', 'business-metrics-south-african-sme-track-weekly', 'You cannot grow what you do not measure. These five metrics give you a clear, weekly picture of the health and direction of your business.', 'Most small business owners are busy. Tracking business metrics feels like an admin task that competes with serving clients, managing staff, and keeping operations running. But the businesses that grow consistently are not the ones working the hardest — they are the ones making the best decisions. And good decisions require reliable data.\n\nYou do not need a complex dashboard or an expensive analytics platform to track the metrics that matter most. These five numbers, reviewed weekly, will give you a better picture of your business health than most formal management reports.\n\n**1. Number of new leads this week**\n\nThis tells you whether your marketing is working. If you are not counting leads, you have no way to know if a change in marketing spend, a new campaign, or a website update is making a difference. Set a target, track weekly, and review the trend monthly.\n\n**2. Lead-to-client conversion rate**\n\nOf the leads that came in this month, what percentage became paying clients? This tells you whether your sales process is working, whether your pricing is positioned correctly, and whether the quality of your leads is improving or declining.\n\n**3. Revenue this week versus the same week last year**\n\nYear-on-year comparison removes seasonal noise and gives you a true picture of growth. Knowing that this week was 20% ahead of the same week twelve months ago is more meaningful than knowing it was better than last week.\n\n**4. Outstanding invoices and cash flow position**\n\nFor South African SMEs, cash flow is frequently the difference between survival and closure. Tracking outstanding invoices weekly — and following up consistently — has a direct impact on your ability to pay staff, suppliers, and yourself.\n\n**5. Website enquiries and conversions**\n\nIf your website is one of your primary lead generation channels, track weekly how many people visited, how many submitted an enquiry, and what your conversion rate was. A sudden drop in conversions is an early warning sign of a technical problem, a Google ranking change, or a competitor move.\n\n**Making it sustainable**\n\nThe goal is not a perfect dashboard — it is a sustainable habit. Even tracking these five numbers in a simple spreadsheet, reviewed every Monday morning, will give you more clarity and control over your business than most owners have.', NULL, 'KConsulting Team', 'Growth', 'Analytics, KPIs, Dashboard, Growth, Business Metrics, SME', 6, 0, 'published', 188, '2026-06-07 09:00:00', '2026-06-08 08:58:53');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` varchar(100) DEFAULT 'meeting',
  `client_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(50) DEFAULT 'scheduled',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `title`, `description`, `event_date`, `event_time`, `event_type`, `client_id`, `project_id`, `created_by`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Project Kickoff Meeting', 'Initial project planning and requirements gathering', '2025-09-13', '10:00:00', 'meeting', 1, 1, 1, '2025-09-11 13:23:34', '2025-09-11 13:23:34', 'scheduled'),
(2, 'Client Presentation', 'Present final project deliverables to client', '2025-09-16', '14:30:00', 'presentation', 2, 2, 1, '2025-09-11 13:23:34', '2025-09-11 13:23:34', 'scheduled'),
(3, 'Team Sprint Planning', 'Plan upcoming development sprint', '2025-09-14', '09:00:00', 'planning', NULL, 3, 1, '2025-09-11 13:23:34', '2025-09-11 13:23:34', 'scheduled'),
(4, 'Marketing Campaign Review', 'Review Q4 marketing campaign performance', '2025-09-15', '11:00:00', 'review', 3, NULL, 1, '2025-09-11 13:23:35', '2025-09-11 13:23:35', 'scheduled'),
(5, 'Budget Planning Session', 'Plan departmental budget for next quarter', '2025-09-17', '15:00:00', 'planning', NULL, NULL, 1, '2025-09-11 13:23:35', '2025-09-11 13:23:35', 'scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

DROP TABLE IF EXISTS `candidates`;
CREATE TABLE IF NOT EXISTS `candidates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_posting_id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resume_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_letter` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'applied',
  `application_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_posting_id` (`job_posting_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `job_posting_id`, `first_name`, `last_name`, `email`, `phone`, `resume_file`, `cover_letter`, `status`, `application_date`, `created_at`) VALUES
(1, 1, 'Sarah', 'Johnson', 'sarah.johnson@email.com', '+1-555-0123', NULL, 'I am excited to apply for this position. With 5 years of experience in software development, I believe I would be a valuable addition to your team.', 'applied', '2025-09-11 13:22:29', '2025-09-11 13:22:29'),
(2, 1, 'Michael', 'Chen', 'michael.chen@email.com', '+1-555-0456', NULL, 'Dear Hiring Manager, I am writing to express my interest in this role. My background in computer science and industry experience have prepared me well for this opportunity.', 'applied', '2025-09-11 13:22:29', '2025-09-11 13:22:29'),
(3, 2, 'Emma', 'Davis', 'emma.davis@email.com', '+1-555-0789', NULL, 'I am thrilled to apply for this marketing position. With my degree in Marketing and digital marketing experience, I have successfully managed campaigns that increased brand awareness.', 'reviewed', '2025-09-11 13:22:30', '2025-09-11 13:22:30');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_education`
--

DROP TABLE IF EXISTS `candidate_education`;
CREATE TABLE IF NOT EXISTS `candidate_education` (
  `id` int NOT NULL AUTO_INCREMENT,
  `candidate_id` int NOT NULL,
  `institution_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `degree_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_of_study` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_year` int DEFAULT NULL,
  `end_year` int DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `gpa` decimal(3,2) DEFAULT NULL,
  `honors` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `candidate_id` (`candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_work_experience`
--

DROP TABLE IF EXISTS `candidate_work_experience`;
CREATE TABLE IF NOT EXISTS `candidate_work_experience` (
  `id` int NOT NULL AUTO_INCREMENT,
  `candidate_id` int NOT NULL,
  `company_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `responsibilities` text COLLATE utf8mb4_unicode_ci,
  `achievements` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `candidate_id` (`candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'prospect',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `company`, `address`, `status`, `created_at`) VALUES
(1, 'TechCorp', 'contact@techcorp.com', NULL, 'TechCorp Solutions Inc.', NULL, 'active', '2025-09-11 10:40:33'),
(2, 'GreenEnergy', 'info@greenenergy.com', NULL, 'GreenEnergy Solutions', NULL, 'active', '2025-09-11 10:40:33'),
(3, 'RetailPlus', 'sales@retailplus.com', NULL, 'RetailPlus Ltd', NULL, 'prospect', '2025-09-11 10:40:33'),
(4, 'TechCorp Solutions', 'contact@techcorp.com', '+1-555-0101', 'TechCorp Solutions Inc.', '123 Tech Street, Silicon Valley, CA 94000', 'active', '2025-09-11 10:53:23'),
(5, 'Green Energy Ltd', 'info@greenenergy.com', '+1-555-0102', 'Green Energy Limited', '456 Solar Avenue, Austin, TX 73000', 'active', '2025-09-11 10:53:23'),
(6, 'Fashion Forward', 'hello@fashionforward.com', '+1-555-0103', 'Fashion Forward LLC', '789 Style Boulevard, New York, NY 10001', 'prospect', '2025-09-11 10:53:24'),
(7, 'HealthTech Innovations', 'contact@healthtech.com', '+1-555-0104', 'HealthTech Innovations Corp', '321 Medical Plaza, Boston, MA 02101', 'active', '2025-09-11 10:53:24'),
(8, 'EduLearn Platform', 'support@edulearn.com', '+1-555-0105', 'EduLearn Educational Services', '654 Learning Lane, Chicago, IL 60601', 'prospect', '2025-09-11 10:53:25'),
(9, 'RetailMax Chain', 'business@retailmax.com', '+1-555-0106', 'RetailMax Chain Stores', '987 Commerce Drive, Miami, FL 33101', 'active', '2025-09-11 10:53:25'),
(10, 'StartupX', 'founders@startupx.com', '+1-555-0107', 'StartupX Technologies', '147 Innovation Hub, Seattle, WA 98101', 'prospect', '2025-09-11 10:53:25'),
(11, 'Global Ride (Pty)Ltd', '', '+1-555-0108', 'Manufacturing Plus Industries', '258 Industrial Park, Detroit, MI 48201', 'prospect', '2025-09-11 10:53:26');

-- --------------------------------------------------------

--
-- Table structure for table `client_contacts`
--

DROP TABLE IF EXISTS `client_contacts`;
CREATE TABLE IF NOT EXISTS `client_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_to` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `fk_contact_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_meetings`
--

DROP TABLE IF EXISTS `client_meetings`;
CREATE TABLE IF NOT EXISTS `client_meetings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `meeting_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_date` datetime NOT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agenda` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_requests`
--

DROP TABLE IF EXISTS `consultation_requests`;
CREATE TABLE IF NOT EXISTS `consultation_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company` varchar(255) NOT NULL,
  `industry` varchar(100) NOT NULL,
  `company_size` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `services` varchar(100) NOT NULL,
  `consultation_type` varchar(100) NOT NULL,
  `timeline` varchar(50) NOT NULL,
  `budget` varchar(50) NOT NULL,
  `current_challenges` text NOT NULL,
  `desired_outcomes` text NOT NULL,
  `current_systems` text,
  `decision_maker` varchar(50) NOT NULL,
  `decision_timeline` varchar(50) NOT NULL,
  `competitors` varchar(50) DEFAULT NULL,
  `meeting_type` varchar(50) NOT NULL,
  `preferred_location` varchar(100) DEFAULT NULL,
  `availability` varchar(100) DEFAULT NULL,
  `additional_info` text,
  `qualification_score` int NOT NULL,
  `submitted_at` datetime NOT NULL,
  `assigned_to` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_consultation_assigned` (`assigned_to`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `consultation_requests`
--

INSERT INTO `consultation_requests` (`id`, `company`, `industry`, `company_size`, `name`, `position`, `email`, `phone`, `services`, `consultation_type`, `timeline`, `budget`, `current_challenges`, `desired_outcomes`, `current_systems`, `decision_maker`, `decision_timeline`, `competitors`, `meeting_type`, `preferred_location`, `availability`, `additional_info`, `qualification_score`, `submitted_at`, `assigned_to`) VALUES
(1, 'KConsulting', 'financial', 'startup', 'KHAYELIHLE SIMELANE', 'ceo', 'mkhayguze@gmail.com', '0653307703', 'it-consulting', 'strategic', 'long', '500k-1m', 'We need resources to embark on the new project related to bank', 'We need a team that can develop, test and mantain a loan system', 'Java, angular, spring boot, .net, camunda, mongo db, sql ', 'yes', 'month', 'few', 'flexible', 'cape-town', 'evening', '', 0, '2025-08-22 10:28:45', NULL),
(2, 'KConsulting Firm', 'other', 'startup', 'MS SINQOBILE C NDLOVU', 'coordinator', 'christobellndlovu@gmail.com', '0698367250', 'marketing', 'strategic', 'immediate', 'under-50k', 'Our pain pointsare our digital marketing.', 'We want to grow our social media through paid strategic marketing campaigns that engage our audience.', 'Non.', 'yes', 'immediate', 'early', 'in-person', 'durban', 'afternoon', '', 0, '2025-08-22 22:37:49', NULL),
(3, 'KConsulting Firm', 'other', 'startup', 'MS SINQOBILE C NDLOVU', 'cto', 'christobellndlovu@gmail.com', '0698367250', 'marketing', 'strategic', 'immediate', 'under-50k', ' b', 'hhb', '', 'influencer', 'month', 'many', 'virtual', 'cape-town', 'afternoon', '', 0, '2025-08-22 23:10:20', NULL),
(4, 'KConsulting', 'healthcare', 'startup', 'KHAYELIHLE SIMELANE', 'coordinator', 'mkhayguze@gmail.com', '0653307703', 'software-development', 'Basic', 'long', 'over-1m', 'No one is building and Improving current solutions', 'Improving and building new healthcare solution', '.Net, Angular, SQL and API', 'yes', 'longer', 'no', 'flexible', 'neutral', 'morning', '', 0, '2025-08-23 16:43:50', NULL),
(5, 'NSFAS', 'government', 'large', 'KHAYELIHLE SIMELANE', 'manager', 'mkhayguze@gmail.com', '0653307703', 'it-consulting', 'Basic', 'medium', '500k-1m', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 'yes', 'month', 'few', 'virtual', 'neutral', 'afternoon', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 0, '2025-09-12 11:24:25', NULL),
(6, 'Sewelara Security &amp; Cleaning (Pty) Ltd', 'Telecommunications', 'Medium (51-200)', 'KHAYELIHLE SIMELANE', 'CEO/President', 'mkhayguze@gmail.com', '0653307703', 'IT Consulting', 'Basic', 'Immediate', 'R150,000 - R500,000', 'Test challenge', 'Desired outcome &amp; goals', 'Current tech and system', 'Yes, I make the final decision', 'Within 2 weeks', 'Yes, considering 1-2 others', 'Flexible', 'Cape Town Office', 'Evening', 'Additional Information', 0, '2026-03-16 21:47:18', NULL),
(7, 'Work With Us Records', 'Financial Services', 'Startup (1-10)', 'MR KHAYELIHLE N SIMELANE', 'CEO/President', 'mkhayguze@gmail.com', '0653307703', 'IT Consulting', 'Basic', 'Immediate', 'Under R50,000', 'This comprehensive form helps us prepare for a productive consultation session', 'This comprehensive form helps us prepare for a productive consultation session', 'This comprehensive form helps us prepare for a productive consultation session', 'Yes, I make the final decision', 'Within 2 weeks', 'No, you&#039;re our preferred choice', 'In-person meeting', 'Cape Town Office', 'Afternoon', 'Complete Your Consultation Request. This comprehensive form helps us prepare for a productive consultation session', 0, '2026-03-16 22:01:36', NULL),
(8, 'KConsulting Firm', 'Government', 'Startup (1-10)', 'Sinqobile Christobel Ndlovu', 'Department Manager', 'christobellndlovu@gmail.com', '0698367250', 'Cybersecurity', 'Strategic', 'Medium-term', 'Over R1,000,000', 'mn', 'jn', 'jn', 'I make recommendations', 'Within 1 month', 'Yes, considering 1-2 others', 'Virtual meeting', 'Cape Town Office', 'Afternoon', 'm,m,', 0, '2026-03-23 15:15:46', NULL),
(9, 'KConsulting Firm', 'Other', 'Medium (51-200)', 'Sinqobile Christobel Ndlovu', 'Coordinator/Specialist', 'christobellndlovu@gmail.com', '0698367250', 'Cloud Services', 'Basic', 'Long-term', 'R150,000 - R500,000', 'm', ',,', 'kn', 'Yes, I make the final decision', 'Within 1 month', 'No, you&#039;re our preferred choice', 'Virtual meeting', 'Johannesburg Office', 'Afternoon', 'kml', 0, '2026-03-29 15:40:25', NULL),
(10, 'Sewelara Security &amp; Cleaning (Pty) Ltd', 'Government', 'Small (11-50)', 'Khaylihle Ndumiso Simelane', 'CEO/President', 'mkhayguze@gmail.com', '0653307703', 'Cloud Services', 'free-consultation', 'Short-term', 'Under R20,000', 'fdhfghfgh', 'gfdghdhj', 'hdgdghfg', 'Yes, I make the final decision', 'Ready now', 'Yes, considering 1-2 others', 'In-person meeting', 'Neutral location', 'Morning', 'dghfjfxgfg', 0, '2026-05-31 12:32:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `custom_reports`
--

DROP TABLE IF EXISTS `custom_reports`;
CREATE TABLE IF NOT EXISTS `custom_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `report_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sql_query` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `diagnostic_responses`
--

DROP TABLE IF EXISTS `diagnostic_responses`;
CREATE TABLE IF NOT EXISTS `diagnostic_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `challenge` varchar(255) DEFAULT NULL,
  `goal` varchar(255) DEFAULT NULL,
  `budget` varchar(100) DEFAULT NULL,
  `team_size` varchar(100) DEFAULT NULL,
  `timeline` varchar(100) DEFAULT NULL,
  `recommendation` text,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `diagnostic_responses`
--

INSERT INTO `diagnostic_responses` (`id`, `name`, `email`, `phone`, `challenge`, `goal`, `budget`, `team_size`, `timeline`, `recommendation`, `submitted_at`) VALUES
(1, 'KHAYELIHLE SIMELANE', 'mkhayguze@gmail.com', '+27653307703', 'No Clear Strategy', 'Improve Customer Retention', 'Flexible', 'Established team', 'Long-term (6+ months)', 'You need our Marketing Strategy Blueprint. We&#039;ll architect a clear plan aligned to your goals and build the systems to execute it.', '2026-06-07 22:33:36');

-- --------------------------------------------------------

--
-- Table structure for table `email_campaigns`
--

DROP TABLE IF EXISTS `email_campaigns`;
CREATE TABLE IF NOT EXISTS `email_campaigns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `marketing_campaign_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_list` text COLLATE utf8mb4_unicode_ci,
  `scheduled_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `sent_count` int DEFAULT '0',
  `open_count` int DEFAULT '0',
  `click_count` int DEFAULT '0',
  `total_recipients` int NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `marketing_campaign_id` (`marketing_campaign_id`),
  KEY `created_by` (`created_by`),
  KEY `fk_email_campaign_client` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_campaigns`
--

INSERT INTO `email_campaigns` (`id`, `marketing_campaign_id`, `client_id`, `subject`, `body`, `content`, `recipient_list`, `scheduled_date`, `status`, `sent_count`, `open_count`, `click_count`, `total_recipients`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'Welcome to Our Summer Collection!', 'Discover the hottest trends for this season. Get 15% off your first purchase with code SUMMER15.', '', 'newsletter@company.com, customers@lists.com', '2024-09-15 06:00:00', 'sent', 2500, 850, 125, 0, 5, '2025-09-11 10:54:20'),
(2, 1, NULL, 'Last Chance: Summer Sale Ending Soon!', 'Don\'t miss out on amazing deals. Sale ends in 48 hours. Shop now before it\'s too late!', '', 'customers@lists.com', '2024-09-28 08:00:00', 'sent', 3200, 1100, 180, 0, 6, '2025-09-11 10:54:21'),
(3, 4, NULL, 'Monthly Newsletter - September 2024', 'Industry insights, company updates, and exclusive tips for our valued subscribers.', '', 'newsletter@lists.com', '2024-09-01 10:00:00', 'sent', 5400, 1890, 340, 0, 5, '2025-09-11 10:54:21'),
(4, 4, NULL, 'Exclusive Member Benefits Update', 'New perks added to your membership! Check out what\'s new and how to access your benefits.', '', 'members@lists.com', '2024-09-10 12:00:00', 'sent', 1800, 720, 95, 0, 6, '2025-09-11 10:54:21'),
(5, 3, NULL, 'Brand Story: Our Journey So Far', 'From startup to industry leader - read about our mission, values, and what drives us forward.', '', 'newsletter@lists.com', '2024-09-20 14:00:00', 'scheduled', 0, 0, 0, 0, 5, '2025-09-11 10:54:22'),
(6, 7, NULL, 'You\'ve Got Rewards to Claim!', 'Thanks for referring your friends! Your reward points are ready. Here\'s how to redeem them.', '', 'rewards@lists.com', '2024-09-25 09:00:00', 'scheduled', 0, 0, 0, 0, 6, '2025-09-11 10:54:22'),
(7, 6, NULL, 'Partnership Opportunity Update', 'Exciting collaboration opportunities await! Learn about our new partnership program.', '', 'partners@lists.com', '2025-09-12 12:46:33', 'draft', 0, 0, 0, 3, 5, '2025-09-11 10:54:23');

-- --------------------------------------------------------

--
-- Table structure for table `email_recipients`
--

DROP TABLE IF EXISTS `email_recipients`;
CREATE TABLE IF NOT EXISTS `email_recipients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_campaign_id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email_campaign_id` (`email_campaign_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_recipients`
--

INSERT INTO `email_recipients` (`id`, `email_campaign_id`, `email`, `name`, `status`, `sent_at`, `opened_at`, `clicked_at`, `created_at`) VALUES
(1, 6, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 06:32:00'),
(2, 7, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 12:43:17'),
(3, 7, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 12:46:04'),
(4, 7, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 12:46:33');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'IT',
  `position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `manager_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `manager_id` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'office',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `expense_date` date NOT NULL,
  `receipt_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `submitted_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `project_id`, `category`, `description`, `amount`, `expense_date`, `receipt_file`, `status`, `approved_by`, `approved_at`, `submitted_by`, `created_at`) VALUES
(4, NULL, 'office', 'Office supplies and equipment', '320.00', '2024-09-20', NULL, 'pending', NULL, NULL, 10, '2025-09-11 10:58:13'),
(6, 5, 'consultation', 'Security expert consultation', '800.00', '2024-09-25', NULL, 'pending', NULL, NULL, 10, '2025-09-11 10:58:14'),
(8, 6, 'hosting', 'AWS infrastructure costs', '420.00', '2024-10-01', NULL, 'pending', NULL, NULL, 9, '2025-09-11 10:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `hr_employees`
--

DROP TABLE IF EXISTS `hr_employees`;
CREATE TABLE IF NOT EXISTS `hr_employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'IT',
  `position` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `manager_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `manager_id` (`manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_employees`
--

INSERT INTO `hr_employees` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `department`, `position`, `salary`, `hire_date`, `status`, `manager_id`, `created_at`) VALUES
(1, 'EMP004', 'Michael', 'Chen', 'michael.chen@company.com', '+1-555-2001', 'IT', 'Senior Developer', '85000.00', '2023-03-15', 'active', NULL, '2025-09-11 10:54:31'),
(2, 'EMP005', 'Jennifer', 'Martinez', 'jennifer.martinez@company.com', '+1-555-2002', 'Marketing', 'Content Specialist', '58000.00', '2023-06-01', 'active', NULL, '2025-09-11 10:54:31'),
(3, 'EMP006', 'Robert', 'Taylor', 'robert.taylor@company.com', '+1-555-2003', 'Finance', 'Senior Accountant', '72000.00', '2023-01-20', 'active', NULL, '2025-09-11 10:54:32'),
(4, 'EMP007', 'Amanda', 'Wilson', 'amanda.wilson@company.com', '+1-555-2004', 'HR', 'HR Coordinator', '55000.00', '2023-09-10', 'active', NULL, '2025-09-11 10:54:32'),
(5, 'EMP008', 'Christopher', 'Davis', 'chris.davis@company.com', '+1-555-2005', 'Clients', 'Account Manager', '68000.00', '2023-04-05', 'active', NULL, '2025-09-11 10:54:33'),
(6, 'EMP009', 'Michelle', 'Garcia', 'michelle.garcia@company.com', '+1-555-2006', 'IT', 'DevOps Engineer', '82000.00', '2023-07-12', 'active', NULL, '2025-09-11 10:54:33'),
(7, 'EMP010', 'Daniel', 'Rodriguez', 'daniel.rodriguez@company.com', '+1-555-2007', 'Marketing', 'Social Media Manager', '52000.00', '2023-08-18', 'active', NULL, '2025-09-11 10:54:34'),
(8, 'EMP011', 'Rachel', 'Lee', 'rachel.lee@company.com', '+1-555-2008', 'Finance', 'Financial Analyst', '64000.00', '2023-02-28', 'active', NULL, '2025-09-11 10:54:34'),
(9, 'EMP012', 'Kevin', 'Anderson', 'kevin.anderson@company.com', '+1-555-2009', 'Clients', 'Customer Success Manager', '61000.00', '2023-05-14', 'active', NULL, '2025-09-11 10:54:34');

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_requests`
--

DROP TABLE IF EXISTS `hr_leave_requests`;
CREATE TABLE IF NOT EXISTS `hr_leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int DEFAULT NULL,
  `leave_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_leave_requests`
--

INSERT INTO `hr_leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `days_requested`, `reason`, `status`, `approved_by`, `approved_at`, `created_at`) VALUES
(7, 1, 'Annual Leave', '2024-10-15', '2024-10-18', 4, 'Family vacation to mountains', 'approved', 7, NULL, '2025-09-11 10:54:35'),
(8, 2, 'Sick Leave', '2024-09-20', '2024-09-22', 3, 'Flu symptoms, doctor recommended rest', 'approved', 7, NULL, '2025-09-11 10:54:35'),
(9, 3, 'Personal Leave', '2024-11-01', '2024-11-01', 1, 'Moving to new apartment', 'approved', 1, NULL, '2025-09-11 10:54:36'),
(10, 4, 'Annual Leave', '2024-12-20', '2024-12-31', 10, 'Christmas and New Year holidays', 'pending', NULL, NULL, '2025-09-11 10:54:36'),
(11, 5, 'Maternity Leave', '2024-11-15', '2025-02-15', 90, 'Expected delivery date approach', 'approved', 7, NULL, '2025-09-11 10:54:37'),
(12, 6, 'Annual Leave', '2024-10-10', '2024-10-12', 3, 'Wedding anniversary celebration', 'approved', 8, NULL, '2025-09-11 10:54:37'),
(13, 7, 'Sick Leave', '2024-09-25', '2024-09-25', 1, 'Medical appointment', 'approved', 8, NULL, '2025-09-11 10:54:38'),
(14, 8, 'Annual Leave', '2024-11-25', '2024-11-29', 5, 'Thanksgiving week with family', 'rejected', 1, NULL, '2025-09-11 10:54:38'),
(15, 9, 'Personal Leave', '2024-10-08', '2024-10-08', 1, 'Child school event', 'approved', 11, NULL, '2025-09-11 10:54:39'),
(19, 5, 'personal', '2025-09-12', '2025-09-25', 14, 'hfdbdfhvdfhdffddh', 'pending', NULL, NULL, '2025-09-12 12:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quotation_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(4,4) NOT NULL DEFAULT '0.1500',
  `vat_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `quotation_id` (`quotation_id`),
  KEY `client_id` (`client_id`),
  KEY `project_id` (`project_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `quotation_id`, `client_id`, `project_id`, `invoice_date`, `due_date`, `status`, `subtotal`, `vat_rate`, `vat_amount`, `total_amount`, `paid_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'INV-2025-DEMO', 4, 1, NULL, '2025-09-11', '2025-10-11', 'draft', '11090.00', '0.0000', '0.00', '11090.00', '0.00', 'Demo invoice converted from QUO-2025-TEST', 1, '2025-09-11 10:40:33', '2025-11-02 12:02:37'),
(3, 'INV-2025-001', 4, 1, 4, '2024-09-18', '2024-10-18', 'sent', '12500.00', '0.1500', '1875.00', '14375.00', '5000.00', 'Partial payment received. Balance due on completion.', 9, '2025-09-11 10:55:14', '2025-09-11 10:55:14'),
(4, 'INV-2025-002', NULL, 3, 3, '2024-09-12', '2024-10-12', 'sent', '8900.00', '0.1500', '1335.00', '10235.00', '0.00', 'Database migration services as per signed agreement.', 10, '2025-09-11 10:55:15', '2025-09-11 10:55:15'),
(5, 'INV-2025-003', 7, 7, 7, '2024-09-30', '2024-10-30', 'paid', '9500.00', '0.1500', '1425.00', '10925.00', '10925.00', 'Analytics dashboard development - converted from quotation.', 10, '2025-09-11 10:55:15', '2025-09-12 10:36:59'),
(6, 'INV-2025-004', NULL, 2, 2, '2024-09-08', '2024-10-08', 'paid', '6800.00', '0.1500', '1020.00', '7820.00', '7820.00', 'Consultation and project planning phase completed.', 9, '2025-09-11 10:55:16', '2025-09-11 10:55:16'),
(7, 'INV-2025-005', NULL, 5, 5, '2024-09-22', '2024-10-22', 'sent', '11250.00', '0.1500', '1687.50', '12937.50', '0.00', 'Security audit phase 1 - vulnerability assessment completed.', 10, '2025-09-11 10:55:16', '2025-09-11 10:55:16');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(10, 3, 'Dashboard Architecture', '25.00', '130.00', '3250.00', '2025-09-11 10:55:20'),
(11, 3, 'Frontend Development', '35.00', '120.00', '4200.00', '2025-09-11 10:55:20'),
(12, 3, 'Data Integration', '20.00', '100.00', '2000.00', '2025-09-11 10:55:21'),
(13, 4, 'Project Consultation', '16.00', '150.00', '2400.00', '2025-09-11 10:55:21'),
(14, 4, 'Technical Specification', '20.00', '125.00', '2500.00', '2025-09-11 10:55:22'),
(15, 4, 'Architecture Planning', '18.00', '100.00', '1800.00', '2025-09-11 10:55:22'),
(16, 5, 'Security Assessment', '45.00', '140.00', '6300.00', '2025-09-11 10:55:23'),
(17, 5, 'Vulnerability Testing', '30.00', '130.00', '3900.00', '2025-09-11 10:55:23'),
(18, 5, 'Compliance Review', '15.00', '120.00', '1800.00', '2025-09-11 10:55:23'),
(19, 6, 'Consulting Services', '4.00', '350.00', '1400.00', '2025-09-11 11:20:36'),
(20, 6, 'Implementation Support', '6.00', '220.00', '1320.00', '2025-09-11 11:20:37'),
(21, 7, 'Consulting Services', '4.00', '350.00', '1400.00', '2025-09-11 11:20:37'),
(22, 7, 'Implementation Support', '6.00', '220.00', '1320.00', '2025-09-11 11:20:38'),
(35, 2, 'Software Development - Phase 1', '1.00', '1500.00', '1500.00', '2025-11-02 12:02:37'),
(36, 2, 'Testing & QA Services', '1.00', '500.00', '500.00', '2025-11-02 12:02:37'),
(37, 2, 'Database Analysis', '20.00', '120.00', '2400.00', '2025-11-02 12:02:37'),
(38, 2, 'Migration Planning', '15.00', '110.00', '1650.00', '2025-11-02 12:02:37'),
(39, 2, 'Data Backup Services', '12.00', '105.00', '1260.00', '2025-11-02 12:02:37'),
(40, 2, 'Initial Migration Phase', '28.00', '135.00', '3780.00', '2025-11-02 12:02:37');

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

DROP TABLE IF EXISTS `job_postings`;
CREATE TABLE IF NOT EXISTS `job_postings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `salary_range` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'full_time',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `posted_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `posted_by` (`posted_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`id`, `title`, `department`, `description`, `requirements`, `salary_range`, `employment_type`, `status`, `posted_by`, `created_at`) VALUES
(1, 'Senior Software Developer', 'IT', 'We are looking for an experienced software developer to join our team.', 'Bachelor degree in Computer Science, 5+ years experience', 'R500,000 - R800,000', 'full_time', 'active', 1, '2025-09-10 20:50:10'),
(2, 'Marketing Manager', 'Marketing', 'Lead our marketing initiatives and campaigns.', 'Marketing degree, 3+ years management experience', 'R400,000 - R600,000', 'full_time', 'active', 1, '2025-09-10 20:50:10'),
(3, 'HR Specialist', 'HR', 'Support HR operations and employee relations.', 'HR qualification, 2+ years experience', 'R300,000 - R450,000', 'full_time', 'active', 1, '2025-09-10 20:50:10'),
(9, 'Senior Software Developer', 'IT', 'We are looking for a Senior Software Developer to join our dynamic IT team. The successful candidate will be responsible for developing, testing, and maintaining software applications that support our business operations.', 'Bachelor\'s degree in Computer Science or related field. 5+ years of experience in software development. Proficiency in PHP, JavaScript, Python, and SQL.', 'R45,000 - R65,000 per month', 'full_time', 'active', 1, '2025-09-11 10:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int DEFAULT NULL,
  `leave_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketing_blog_posts`
--

DROP TABLE IF EXISTS `marketing_blog_posts`;
CREATE TABLE IF NOT EXISTS `marketing_blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `publish_date` date DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `campaign_id` int DEFAULT NULL,
  `author_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketing_blog_posts`
--

INSERT INTO `marketing_blog_posts` (`id`, `title`, `content`, `excerpt`, `featured_image`, `category`, `tags`, `status`, `publish_date`, `client_id`, `campaign_id`, `author_id`, `created_at`, `updated_at`) VALUES
(1, '10 Digital Marketing Trends for 2025', 'The digital marketing landscape continues to evolve at breakneck speed. As we move into 2025, businesses must adapt to new technologies, changing consumer behaviors, and emerging platforms to stay competitive...', NULL, NULL, 'Digital Marketing', 'trends, marketing, 2025, AI', 'published', '2025-09-11', 1, 1, 6, '2025-09-11 13:25:26', '2025-09-12 07:36:20'),
(2, 'Building Brand Authority Through Content Marketing', 'Content marketing remains one of the most effective ways to build brand authority and establish thought leadership in your industry...', NULL, NULL, 'Content Strategy', 'content marketing, brand authority', 'published', '2025-09-08', 2, 2, 7, '2025-09-11 13:25:26', '2025-09-12 07:36:29'),
(3, 'Social Media Marketing Best Practices for Small Businesses', 'Social media marketing can be overwhelming for small businesses with limited resources. However, with the right strategy and focus, small businesses can compete effectively...', NULL, NULL, 'Social Media', 'social media, small business', 'published', '2025-09-04', 3, NULL, 6, '2025-09-11 13:25:27', '2025-09-12 07:38:07'),
(4, 'Email Marketing Automation: A Complete Guide', 'Email marketing automation allows businesses to nurture leads and maintain customer relationships at scale...', '', '', 'technology', 'email marketing, automation', 'scheduled', '2025-09-06', 1, 3, 7, '2025-09-11 13:25:27', '2025-09-12 11:05:58'),
(5, 'The Future of E-commerce: Trends to Watch', 'The e-commerce industry continues to evolve rapidly, driven by technological advances and changing consumer expectations...', NULL, NULL, 'E-commerce', 'ecommerce, future trends', 'published', '2025-09-09', 2, NULL, 5, '2025-09-11 13:25:27', '2025-09-12 07:37:54');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_campaigns`
--

DROP TABLE IF EXISTS `marketing_campaigns`;
CREATE TABLE IF NOT EXISTS `marketing_campaigns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campaign_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'Social Media',
  `campaign_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Social Media',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'planning',
  `budget` decimal(10,2) DEFAULT '0.00',
  `spent` decimal(10,2) DEFAULT '0.00',
  `target_audience` text COLLATE utf8mb4_unicode_ci,
  `metrics` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `fk_campaign_client` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketing_campaigns`
--

INSERT INTO `marketing_campaigns` (`id`, `name`, `campaign_name`, `client_id`, `description`, `type`, `campaign_type`, `status`, `budget`, `spent`, `target_audience`, `metrics`, `start_date`, `end_date`, `created_by`, `created_at`) VALUES
(1, 'Summer Product Launch 2024', 'Summer Product Launch 2024', NULL, 'Multi-channel campaign for new product line launch', 'Product Launch', 'Social Media', 'active', '15000.00', '8500.00', 'Young professionals aged 25-40, tech-savvy consumers', '', '2024-07-01', '2024-09-30', 5, '2025-09-11 10:54:12'),
(2, 'Black Friday Sales Promotion', 'Black Friday Sales Promotion', NULL, 'Seasonal sales campaign with aggressive discounts', 'Seasonal Sales', 'Social Media', 'planning', '25000.00', '2000.00', 'Existing customers and price-conscious shoppers', '', '2024-11-15', '2024-11-30', 5, '2025-09-11 10:54:12'),
(3, 'Brand Awareness Q4 2024', 'Brand Awareness Q4 2024', NULL, 'Increase brand recognition through content marketing', 'Brand Awareness', 'Social Media', 'active', '8000.00', '3200.00', 'Business owners and decision makers', '', '2024-10-01', '2024-12-31', 6, '2025-09-11 10:54:13'),
(4, 'Email Newsletter Automation', 'Email Newsletter Automation', NULL, 'Automated email sequence for customer retention', 'Email Marketing', 'Social Media', 'active', '3000.00', '1800.00', 'Existing customer base, newsletter subscribers', '', '2024-08-15', '2024-12-15', 6, '2025-09-11 10:54:13'),
(5, 'Social Media Growth Initiative', 'Social Media Growth Initiative', NULL, 'Increase social media following and engagement', 'Social Media', 'Social Media', 'active', '5000.00', '2100.00', 'Millennials and Gen Z consumers', '', '2024-09-01', '2024-11-30', 6, '2025-09-11 10:54:14'),
(6, 'Partnership Marketing Program', 'Partnership Marketing Program', NULL, 'Collaborate with industry partners for co-marketing', 'Partnership', 'Social Media', 'planning', '12000.00', '500.00', 'B2B clients and industry professionals', '', '2024-11-01', '2025-01-31', 5, '2025-09-11 10:54:14'),
(7, 'Customer Referral Campaign', 'Customer Referral Campaign', NULL, 'Incentivize existing customers to refer new clients', 'Referral Program', 'Social Media', 'active', '4000.00', '1200.00', 'Happy existing customers', '', '2024-09-15', '2024-12-31', 6, '2025-09-11 10:54:15'),
(8, 'Video Marketing Series', 'Video Marketing Series', NULL, 'Educational video content for YouTube and social media', 'Content Marketing', 'Social Media', 'in-progress', '7500.00', '3100.00', 'Educational content seekers, professionals', '', '2024-08-01', '2024-10-31', 5, '2025-09-11 10:54:15');

-- --------------------------------------------------------

--
-- Table structure for table `money_flow`
--

DROP TABLE IF EXISTS `money_flow`;
CREATE TABLE IF NOT EXISTS `money_flow` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `transaction_date` date NOT NULL,
  `client_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `project_id` (`project_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `money_flow`
--

INSERT INTO `money_flow` (`id`, `transaction_type`, `category`, `amount`, `description`, `transaction_date`, `client_id`, `project_id`, `invoice_id`, `created_by`, `created_at`) VALUES
(1, 'income', 'Payment', '10925.00', 'Payment for invoice INV-2025-003', '2025-09-12', 7, NULL, 5, 1, '2025-09-12 10:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

DROP TABLE IF EXISTS `performance_reviews`;
CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `overall_rating` int DEFAULT '3',
  `goals_achievement` text COLLATE utf8mb4_unicode_ci,
  `strengths` text COLLATE utf8mb4_unicode_ci,
  `areas_for_improvement` text COLLATE utf8mb4_unicode_ci,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `reviewer_id` (`reviewer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_reviews`
--

INSERT INTO `performance_reviews` (`id`, `employee_id`, `reviewer_id`, `review_period_start`, `review_period_end`, `overall_rating`, `goals_achievement`, `strengths`, `areas_for_improvement`, `comments`, `status`, `created_at`) VALUES
(1, 7, 8, '0000-00-00', '0000-00-00', 3, 'gnvcxv', 'vncxv', 'ncbvcxv', 'ncvxvv', 'draft', '2025-09-12 12:34:59'),
(2, 5, 5, '2025-09-01', '2025-09-12', 3, 'vzdxcvxcv', 'cvxc', 'cxvcvx', 'cvxcv', 'draft', '2025-09-12 12:38:51');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_extras`
--

DROP TABLE IF EXISTS `portfolio_extras`;
CREATE TABLE IF NOT EXISTS `portfolio_extras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL COMMENT 'Links to projects.id in the CRM',
  `display_category` varchar(50) DEFAULT NULL COMMENT 'Portfolio filter: web, ecommerce, marketing, systems, it (overrides CRM category if set)',
  `image_url` varchar(500) DEFAULT NULL COMMENT 'Full URL or relative path to the project screenshot',
  `tags` varchar(500) DEFAULT NULL COMMENT 'Comma-separated skills or tools used (e.g. WordPress, SEO, PHP)',
  `badge_label` varchar(100) DEFAULT NULL COMMENT 'Highlight label on the card (e.g. Featured, New, Award Winner)',
  `badge_colour` varchar(30) DEFAULT 'gold' COMMENT 'Badge colour: gold, green, blue, red, grey',
  `case_study_title` varchar(255) DEFAULT NULL COMMENT 'Heading shown when the case study popup opens',
  `case_study_overview` text COMMENT 'Brief context: what the project was and who it was for',
  `case_study_challenge` text COMMENT 'The problem or pain point the client was facing',
  `case_study_solution` text COMMENT 'What KConsulting built or implemented to solve it',
  `case_study_results` text COMMENT 'Measurable outcomes: metrics, improvements, business impact',
  `project_live_url` varchar(500) DEFAULT NULL COMMENT 'Link to the live website (shown as a button in the popup)',
  `show_in_portfolio` tinyint(1) DEFAULT '1' COMMENT '1 = visible on portfolio page, 0 = hidden without deleting',
  `sort_order` int DEFAULT '0' COMMENT 'Display order: lower number appears first on the page',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `portfolio_extras`
--

INSERT INTO `portfolio_extras` (`id`, `project_id`, `display_category`, `image_url`, `tags`, `badge_label`, `badge_colour`, `case_study_title`, `case_study_overview`, `case_study_challenge`, `case_study_solution`, `case_study_results`, `project_live_url`, `show_in_portfolio`, `sort_order`, `created_at`) VALUES
(1, 1, 'ecommerce', NULL, 'WooCommerce, UX, Payment Gateway, CRO, Mobile', 'Featured', 'gold', 'E-Commerce Platform Redesign', 'A complete redesign and rebuild of a client e-commerce platform to improve conversions, modernise the shopping experience, and support higher traffic volumes.', 'The existing platform was outdated, had a high cart abandonment rate, and performed poorly on mobile devices. Clients were losing sales to competitors with more modern stores.', 'We redesigned the product pages, simplified the checkout to three steps, integrated a reliable payment gateway, and rebuilt the mobile experience from the ground up with performance as a priority.', 'Cart abandonment dropped from 72% to 44%.\nMobile conversion rate increased by 85%.\nRevenue grew 40% month-on-month in the first quarter post-launch.', NULL, 1, 1, '2026-06-08 08:43:52'),
(2, 2, 'web', NULL, 'iOS, Android, React Native, API, CRM Integration', 'New', 'green', 'Mobile App for Business Management', 'A native iOS and Android app built to give a client\'s team real-time access to their business data, client records, and task management from any device.', 'The client\'s team was working from spreadsheets and desktop-only tools, making it impossible to access information or update records while out of the office.', 'We designed and built a cross-platform mobile app with secure login, real-time data sync, push notifications, and full CRM integration so the team could manage everything on the go.', 'Team productivity improved by 30% in the first 60 days.\nField staff response times reduced by 50%.\nApp store rating of 4.8 from internal users.', NULL, 1, 2, '2026-06-08 08:43:52'),
(3, 3, 'it', NULL, 'MySQL, Database, Migration, Data Integrity, SQL', NULL, 'gold', 'Legacy Database Migration to MySQL', 'Migrated a client\'s decade-old legacy database to a modern, optimised MySQL infrastructure with zero data loss and minimal downtime.', 'The legacy system was slow, prone to errors, and no longer supported. Data was spread across inconsistent formats, making querying unreliable and reporting impossible.', 'We audited the existing data, designed a clean relational schema, wrote custom migration scripts to transform and validate all records, and ran parallel testing before the final cutover.', '100% data integrity confirmed post-migration.\nQuery performance improved by 300% on key reports.\nSystem downtime during migration was under 2 hours.', NULL, 1, 3, '2026-06-08 08:43:52'),
(4, 4, 'systems', NULL, 'API, REST, Payment Gateway, Shipping, Webhooks, PHP', 'Featured', 'blue', 'Third-Party API Integration System', 'Built a unified integration layer connecting payment, shipping, and inventory APIs for a retail client, replacing a fragmented manual process with a single automated system.', 'The client was using three separate tools with no integration between them. Staff were manually copying order data between systems, leading to delays, errors, and customer complaints.', 'We designed and built a REST API integration hub using PHP and webhooks, connecting the payment gateway, shipping provider, and inventory system so data flows automatically with full error handling and logging.', 'Order processing time reduced from 45 minutes to under 2 minutes.\nData entry errors eliminated entirely.\nStaff saved approximately 20 hours per week in manual admin.', NULL, 1, 4, '2026-06-08 08:43:52'),
(5, 5, 'it', NULL, 'Security, GDPR, Compliance, Penetration Testing, Audit', NULL, 'gold', 'Security Audit and GDPR Compliance', 'Conducted a full security review and GDPR compliance implementation for a client handling sensitive customer data, identifying vulnerabilities and delivering a compliant, secure setup.', 'The client was collecting and storing customer data without proper GDPR controls in place, creating legal risk. Their systems had not been audited and had several unpatched vulnerabilities.', 'We performed a penetration test, reviewed all data handling processes, implemented encryption at rest and in transit, updated cookie and privacy policies, and introduced role-based access controls.', '12 security vulnerabilities identified and resolved.\nFull GDPR compliance achieved before regulatory deadline.\nClient passed third-party compliance audit with no findings.', NULL, 1, 5, '2026-06-08 08:43:52'),
(6, 6, 'it', NULL, 'AWS, DevOps, Cloud, Auto-scaling, Docker, Linux', NULL, 'gold', 'Cloud Infrastructure Setup on AWS', 'Designed and deployed a fully managed AWS cloud infrastructure with auto-scaling, load balancing, and automated backups for a growing SaaS business.', 'The client\'s on-premise setup could not handle traffic spikes, causing outages during peak periods. They needed a scalable, fault-tolerant architecture that grew with their user base.', 'We architected a multi-AZ AWS setup with EC2 auto-scaling groups, an Application Load Balancer, RDS with automated backups, CloudWatch monitoring, and Docker containerisation for consistent deployments.', 'Zero downtime during a 3x traffic spike post-launch.\nInfrastructure costs reduced by 32% versus on-premise.\nDeployment time cut from 2 hours to 8 minutes with CI/CD pipeline.', NULL, 1, 6, '2026-06-08 08:43:52'),
(7, 7, 'web', NULL, 'Dashboard, Analytics, PHP, MySQL, Chart.js, UX', NULL, 'gold', 'Custom Business Intelligence Dashboard', 'Built a custom internal analytics dashboard giving leadership real-time visibility into sales, project progress, team performance, and revenue KPIs from a single screen.', 'Management was pulling data from five separate tools to compile weekly reports, taking 3-4 hours each time. Decisions were being made on data that was days old.', 'We built a custom PHP and MySQL dashboard with live data feeds, interactive Chart.js visualisations, role-based access, and automated daily email summaries so leadership always had current numbers.', 'Weekly reporting time reduced from 4 hours to zero (fully automated).\nData freshness improved from 48-hour lag to real-time.\nLeadership reported faster, more confident decision-making within 30 days.', NULL, 1, 7, '2026-06-08 08:43:52'),
(8, 8, 'systems', NULL, 'PHP, Laravel, Refactoring, API, MySQL, Modernisation', NULL, 'gold', 'Legacy PHP System Modernisation', 'Modernised a decade-old PHP system into a maintainable, secure, and scalable Laravel application without disrupting day-to-day business operations.', 'The existing system was built on unsupported PHP versions, had no unit tests, and was impossible to extend or maintain safely. Adding any new feature risked breaking existing functionality.', 'We broke the migration into phases, rewriting modules incrementally into Laravel with full test coverage, a clean API layer, and a modern database schema while keeping the old system running in parallel during transition.', 'New features now delivered in days instead of weeks.\nBug rate dropped by 80% within two months of go-live.\nSystem now fully supported and extensible for future growth.', NULL, 1, 8, '2026-06-08 08:43:52');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `client_id` int DEFAULT NULL,
  `category` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'Web Dev',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `progress` int DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `client_id`, `category`, `priority`, `progress`, `status`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'E-Commerce Website Redesign', 'Complete redesign of client e-commerce platform with modern UI/UX', 1, 'Web Dev', 'high', 75, 'completed', '2024-09-01', '2024-11-15', '2025-09-11 10:53:39'),
(2, 'Mobile App Development', 'Native iOS and Android app for client business management', 2, 'Mobile Dev', 'high', 40, 'completed', '2024-08-15', '2024-12-01', '2025-09-11 10:53:39'),
(3, 'Database Migration Project', 'Migrate legacy database to modern MySQL infrastructure', 3, 'Database', 'medium', 90, 'completed', '2024-07-01', '2024-10-30', '2025-09-11 10:53:40'),
(4, 'API Integration System', 'Integrate third-party payment and shipping APIs', 4, 'Integration', 'medium', 60, 'completed', '2024-09-10', '2024-11-30', '2025-09-11 10:53:40'),
(5, 'Security Audit & Compliance', 'Complete security review and GDPR compliance implementation', 5, 'Security', 'high', 25, 'completed', '2024-09-20', '2024-12-15', '2025-09-11 10:53:41'),
(6, 'Cloud Infrastructure Setup', 'Migrate to AWS cloud infrastructure with auto-scaling', 6, 'DevOps', 'medium', 80, 'completed', '2024-08-01', '2024-10-15', '2025-09-11 10:53:41'),
(7, 'Internal Dashboard Development', 'Custom analytics dashboard for business intelligence', 7, 'Web Dev', 'low', 15, 'completed', '2024-10-01', '2024-12-30', '2025-09-11 10:53:41'),
(8, 'Legacy System Modernization', 'Upgrade old PHP system to modern framework', 8, 'Modernization', 'high', 5, 'completed', '2024-11-01', '2025-02-28', '2025-09-11 10:53:42');

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

DROP TABLE IF EXISTS `project_assignments`;
CREATE TABLE IF NOT EXISTS `project_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Developer',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `project_id`, `user_id`, `role`, `assigned_at`) VALUES
(2, 1, 3, 'Frontend Developer', '2025-09-11 10:53:43'),
(3, 1, 4, 'Backend Developer', '2025-09-11 10:53:43'),
(5, 2, 3, 'Mobile Developer', '2025-09-11 10:53:44'),
(6, 2, 4, 'UI/UX Designer', '2025-09-11 10:53:45'),
(8, 3, 3, 'DevOps Engineer', '2025-09-11 10:53:46'),
(9, 4, 3, 'API Developer', '2025-09-11 10:53:46'),
(10, 4, 4, 'Integration Specialist', '2025-09-11 10:53:46'),
(12, 5, 4, 'Compliance Officer', '2025-09-11 10:53:47'),
(13, 6, 3, 'Cloud Architect', '2025-09-11 10:53:48'),
(14, 6, 4, 'DevOps Engineer', '2025-09-11 10:53:48'),
(15, 7, 3, 'Full Stack Developer', '2025-09-11 10:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `project_comments`
--

DROP TABLE IF EXISTS `project_comments`;
CREATE TABLE IF NOT EXISTS `project_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_blocker` tinyint(1) DEFAULT '0',
  `parent_comment_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_comment_id` (`parent_comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_comments`
--

INSERT INTO `project_comments` (`id`, `project_id`, `user_id`, `comment`, `is_blocker`, `parent_comment_id`, `created_at`) VALUES
(2, 1, 3, 'Frontend development is 60% complete. Need API endpoints for user authentication.', 0, NULL, '2025-09-11 10:53:50'),
(3, 1, 4, 'Backend API development on track. Database schema finalized.', 0, NULL, '2025-09-11 10:53:51'),
(4, 1, 3, 'BLOCKER: Waiting for client to provide product images and content.', 1, NULL, '2025-09-11 10:53:51'),
(6, 2, 3, 'iOS version in development. Need testing on latest iOS versions.', 0, NULL, '2025-09-11 10:53:52'),
(7, 2, 4, 'BLOCKER: Waiting for App Store developer account credentials.', 1, NULL, '2025-09-11 10:53:52'),
(9, 3, 3, 'BLOCKER: Need client approval for maintenance window scheduling.', 1, NULL, '2025-09-11 10:53:53'),
(10, 4, 3, 'Payment gateway integration 70% complete.', 0, NULL, '2025-09-11 10:53:54'),
(11, 4, 4, 'Shipping API testing in progress. Documentation review needed.', 0, NULL, '2025-09-11 10:53:54'),
(13, 5, 4, 'GDPR compliance documentation in review.', 0, NULL, '2025-09-11 10:53:55'),
(14, 6, 3, 'AWS infrastructure setup complete. Auto-scaling configured.', 0, NULL, '2025-09-11 10:53:56'),
(15, 7, 3, 'Dashboard wireframes completed. Starting development.', 0, NULL, '2025-09-11 10:53:56'),
(17, 7, 1, 'gndnxgfhxdfg', 0, 15, '2025-09-12 06:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `project_revenues`
--

DROP TABLE IF EXISTS `project_revenues`;
CREATE TABLE IF NOT EXISTS `project_revenues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `client_id` int NOT NULL,
  `revenue_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'milestone',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `received_date` date NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'bank_transfer',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `client_id` (`client_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_revenues`
--

INSERT INTO `project_revenues` (`id`, `project_id`, `client_id`, `revenue_type`, `amount`, `received_date`, `payment_method`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'milestone', '5000.00', '2024-09-10', 'bank_transfer', 'TXN-001-2024', 'First milestone payment for website redesign project', 9, '2025-09-11 10:58:00'),
(2, 2, 2, 'initial_payment', '7500.00', '2024-09-15', 'credit_card', 'CC-002-2024', 'Initial payment for mobile app development', 9, '2025-09-11 10:58:00'),
(3, 3, 3, 'final_payment', '8900.00', '2024-09-20', 'wire_transfer', 'WIRE-003-2024', 'Final payment for database migration completion', 10, '2025-09-11 10:58:01'),
(4, 1, 1, 'milestone', '7500.00', '2024-09-25', 'bank_transfer', 'TXN-004-2024', 'Second milestone payment - frontend completion', 9, '2025-09-11 10:58:01'),
(5, 4, 4, 'milestone', '6200.00', '2024-09-28', 'check', 'CHK-005-2024', 'API integration milestone payment', 10, '2025-09-11 10:58:01'),
(6, 6, 6, 'initial_payment', '5500.00', '2024-10-01', 'bank_transfer', 'TXN-006-2024', 'Cloud infrastructure setup initial payment', 9, '2025-09-11 10:58:02'),
(7, 2, 2, 'milestone', '9200.00', '2024-10-03', 'credit_card', 'CC-007-2024', 'Mobile app beta version completion', 10, '2025-09-11 10:58:02'),
(8, 5, 5, 'milestone', '8800.00', '2024-10-05', 'wire_transfer', 'WIRE-008-2024', 'Security audit phase 1 completion', 9, '2025-09-11 10:58:03'),
(9, 7, 7, 'initial_payment', '3500.00', '2024-10-08', 'bank_transfer', 'TXN-009-2024', 'Dashboard development kickoff payment', 10, '2025-09-11 10:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(4,4) NOT NULL DEFAULT '0.1500',
  `vat_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `project_id` (`project_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `supplier_name`, `supplier_email`, `supplier_phone`, `project_id`, `status`, `order_date`, `expected_delivery`, `subtotal`, `vat_rate`, `vat_amount`, `total_amount`, `notes`, `created_by`, `created_at`) VALUES
(1, 'PO-2025-001', 'Office Supplies Ltd', NULL, NULL, NULL, 'approved', '2025-09-11', '2025-09-18', '1800.00', '0.1500', '270.00', '2070.00', 'Monthly office supplies and equipment for the team', 1, '2025-09-11 10:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 'Ergonomic Office Chairs', '6.00', '150.00', '900.00', '2025-09-11 10:40:34'),
(2, 1, 'Standing Desk Converters', '4.00', '125.00', '500.00', '2025-09-11 10:40:34'),
(3, 1, 'Monitor Arms (Dual)', '5.00', '80.00', '400.00', '2025-09-11 10:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_responses`
--

DROP TABLE IF EXISTS `quiz_responses`;
CREATE TABLE IF NOT EXISTS `quiz_responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `result_type` varchar(100) DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `quiz_responses`
--

INSERT INTO `quiz_responses` (`id`, `name`, `email`, `phone`, `answers`, `result_type`, `submitted_at`) VALUES
(1, 'KHAYELIHLE SIMELANE', 'mkhayguze@gmail.com', '+27653307703', '[{\"category\": \"seo\", \"question\": 1, \"selected\": \"Not getting enough website visitors\"}, {\"category\": \"conversion\", \"question\": 2, \"selected\": \"Outdated – needs a full redesign\"}, {\"category\": \"leads\", \"question\": 3, \"selected\": \"More qualified leads coming in consistently\"}]', 'seo', '2026-06-07 22:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE IF NOT EXISTS `quotations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `vat_rate` decimal(4,4) NOT NULL DEFAULT '0.1500',
  `vat_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `converted_invoice_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_number` (`quotation_number`),
  KEY `client_id` (`client_id`),
  KEY `project_id` (`project_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quotation_number`, `client_id`, `project_id`, `quotation_date`, `valid_until`, `status`, `subtotal`, `vat_rate`, `vat_amount`, `total_amount`, `notes`, `created_by`, `created_at`, `updated_at`, `converted_invoice_id`) VALUES
(4, 'QUO-2025-TEST', 1, NULL, '2025-09-11', '2025-10-11', 'completed', '2000.00', '0.1500', '300.00', '2300.00', 'Test quotation for conversion demo', 1, '2025-09-11 10:40:33', '2025-09-11 10:40:34', 2),
(5, 'QUO-2025-001', 1, 1, '2024-09-01', '2024-10-01', 'sent', '12500.00', '0.1500', '1875.00', '14375.00', 'Website redesign project including responsive design, SEO optimization, and content management system.', 9, '2025-09-11 10:55:04', '2025-09-11 10:55:04', NULL),
(6, 'QUO-2025-002', 2, 2, '2024-09-05', '2024-10-05', 'draft', '18750.00', '0.1500', '2812.50', '21562.50', 'Mobile application development for iOS and Android platforms with backend API integration.', 9, '2025-09-11 10:55:05', '2025-09-11 10:55:05', NULL),
(7, 'QUO-2025-003', 3, 3, '2024-09-10', '2024-10-10', 'sent', '8900.00', '0.1500', '1335.00', '10235.00', 'Database migration and optimization services including data backup and recovery procedures.', 10, '2025-09-11 10:55:05', '2025-09-11 10:55:05', NULL),
(8, 'QUO-2025-004', 4, 4, '2024-09-15', '2024-10-15', 'accepted', '15200.00', '0.1500', '2280.00', '17480.00', 'API integration services for payment processing, shipping, and inventory management systems.', 9, '2025-09-11 10:55:06', '2025-09-11 10:55:06', NULL),
(9, 'QUO-2025-005', 5, 5, '2024-09-20', '2024-10-20', 'draft', '22100.00', '0.1500', '3315.00', '25415.00', 'Comprehensive security audit and GDPR compliance implementation for enterprise systems.', 10, '2025-09-11 10:55:06', '2025-09-11 10:55:06', NULL),
(10, 'QUO-2025-006', 6, 6, '2024-09-25', '2024-10-25', 'sent', '13800.00', '0.1500', '2070.00', '15870.00', 'Cloud infrastructure setup and migration to AWS with auto-scaling and monitoring.', 9, '2025-09-11 10:55:07', '2025-09-11 10:55:07', NULL),
(11, 'QUO-2025-007', 7, 7, '2024-09-28', '2024-10-28', 'accepted', '9500.00', '0.1500', '1425.00', '10925.00', 'Custom analytics dashboard development with real-time reporting capabilities.', 10, '2025-09-11 10:55:07', '2025-09-11 10:55:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quotation_id` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `quotation_id` (`quotation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 4, 'Software Development - Phase 1', '1.00', '1500.00', '1500.00', '2025-09-11 10:40:33'),
(2, 4, 'Testing & QA Services', '1.00', '500.00', '500.00', '2025-09-11 10:40:33'),
(14, 4, 'Payment Gateway Integration', '25.00', '140.00', '3500.00', '2025-09-11 10:55:13'),
(15, 4, 'Shipping API Implementation', '20.00', '120.00', '2400.00', '2025-09-11 10:55:13'),
(16, 4, 'Inventory Management System', '35.00', '135.00', '4725.00', '2025-09-11 10:55:14'),
(17, 4, 'Testing & Quality Assurance', '18.00', '85.00', '1530.00', '2025-09-11 10:55:14'),
(18, 5, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:29'),
(19, 5, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:30'),
(20, 6, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:30'),
(21, 6, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:31'),
(22, 7, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:31'),
(23, 7, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:32'),
(24, 7, 'User Interface Design', '12.00', '95.00', '1140.00', '2025-09-11 11:20:32'),
(25, 8, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:33'),
(26, 8, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:33'),
(27, 9, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:34'),
(28, 9, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:34'),
(29, 10, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:34'),
(30, 10, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:35'),
(31, 11, 'Website Development Services', '1.00', '2500.00', '2500.00', '2025-09-11 11:20:35'),
(32, 11, 'Database Setup & Configuration', '8.00', '125.00', '1000.00', '2025-09-11 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `social_media_posts`
--

DROP TABLE IF EXISTS `social_media_posts`;
CREATE TABLE IF NOT EXISTS `social_media_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `campaign_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_for` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `engagement_stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `created_by` (`created_by`),
  KEY `fk_social_post_client` (`client_id`)
) ;

--
-- Dumping data for table `social_media_posts`
--

INSERT INTO `social_media_posts` (`id`, `campaign_id`, `client_id`, `platform`, `content`, `image_url`, `scheduled_for`, `status`, `engagement_stats`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'Facebook', 'Check out our new summer collection! Perfect for the season ahead. #SummerStyle #NewArrivals', NULL, '2024-09-15 08:00:00', 'published', NULL, 5, '2025-09-11 10:54:16'),
(2, 1, NULL, 'Instagram', 'Behind the scenes of our product photoshoot! 📸 What\'s your favorite piece from our new collection?', NULL, '2024-09-16 12:30:00', 'published', NULL, 6, '2025-09-11 10:54:16'),
(3, 1, NULL, 'Twitter', 'Summer sale starts NOW! Get 20% off all items. Limited time offer. #SummerSale #LimitedOffer', NULL, '2024-09-17 07:00:00', 'published', NULL, 5, '2025-09-11 10:54:16'),
(4, 1, NULL, 'LinkedIn', 'We\'re excited to announce the launch of our innovative product line, designed with modern professionals in mind.', NULL, '2024-09-18 09:00:00', 'scheduled', NULL, 6, '2025-09-11 10:54:17'),
(5, 3, NULL, 'Facebook', 'What makes a brand memorable? Our latest blog post explores the psychology of brand recognition.', NULL, '2024-09-20 11:00:00', 'scheduled', NULL, 5, '2025-09-11 10:54:17'),
(6, 3, NULL, 'Instagram', 'Brand storytelling matters! 📖 Here\'s how we craft authentic stories that resonate with our audience.', NULL, '2024-09-21 14:00:00', 'scheduled', NULL, 6, '2025-09-11 10:54:18'),
(7, 5, NULL, 'TikTok', 'Quick tip Tuesday! Here\'s a 30-second productivity hack that will change your workday. #ProductivityTips', NULL, '2024-09-22 17:00:00', 'draft', NULL, 6, '2025-09-11 10:54:18'),
(8, 5, NULL, 'Instagram', 'Community spotlight! 🌟 Featuring amazing content from our followers. Tag us to be featured next!', NULL, '2024-09-23 10:00:00', 'scheduled', NULL, 5, '2025-09-11 10:54:19'),
(9, 7, NULL, 'Facebook', 'Refer a friend and you both save! Our referral program is now live. Win-win for everyone! 🤝', NULL, '2024-09-25 08:30:00', 'scheduled', NULL, 6, '2025-09-11 10:54:19'),
(10, 8, NULL, 'YouTube', 'New video alert! Learn the top 5 strategies for effective time management in our latest tutorial.', NULL, '2024-09-26 13:00:00', 'draft', NULL, 5, '2025-09-11 10:54:20');

-- --------------------------------------------------------

--
-- Table structure for table `system_activity`
--

DROP TABLE IF EXISTS `system_activity`;
CREATE TABLE IF NOT EXISTS `system_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `department` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_activity` (`user_id`,`created_at`),
  KEY `idx_department_activity` (`department`,`created_at`),
  KEY `idx_activity_type` (`activity_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'employee',
  `department` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'IT',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `department`, `created_at`) VALUES
(1, 'admin', 'admin@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'admin', 'IT', '2025-09-11 10:40:32'),
(3, 'john_manager', 'john@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'IT', '2025-09-11 10:53:18'),
(4, 'sarah_dev', 'sarah@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'IT', '2025-09-11 10:53:18'),
(5, 'mike_bd', 'mike@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'BD', '2025-09-11 10:53:19'),
(6, 'lisa_marketing', 'lisa@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'Marketing', '2025-09-11 10:53:19'),
(7, 'tom_marketing', 'tom@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'Marketing', '2025-09-11 10:53:20'),
(8, 'jane_hr', 'jane@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'HR', '2025-09-11 10:53:20'),
(9, 'bob_hr', 'bob@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'HR', '2025-09-11 10:53:20'),
(10, 'emma_finance', 'emma@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'Finance', '2025-09-11 10:53:21'),
(11, 'david_finance', 'david@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'Finance', '2025-09-11 10:53:21'),
(12, 'anna_clients', 'anna@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'Clients', '2025-09-11 10:53:22'),
(13, 'peter_clients', 'peter@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'Clients', '2025-09-11 10:53:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

DROP TABLE IF EXISTS `user_activities`;
CREATE TABLE IF NOT EXISTS `user_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text,
  `page_url` varchar(255) DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `session_id` varchar(255) DEFAULT NULL,
  `additional_data` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_resource` (`resource_type`,`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1192 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(1, 1, 'admin_user', 'login', 'User logged in successfully', '/auth/login.php', 'user', 1, '192.168.1.100', NULL, NULL, NULL, '2024-01-15 07:30:00'),
(2, 2, 'manager_john', 'create', 'Created new project', '/projects/create.php', 'project', 101, '192.168.1.101', NULL, NULL, NULL, '2024-01-15 08:15:00'),
(3, 1, 'admin_user', 'update', 'Updated user permissions', '/admin/users/edit.php', 'user', 2, '192.168.1.100', NULL, NULL, NULL, '2024-01-15 09:20:00'),
(4, 3, 'user_sarah', 'view', 'Viewed dashboard', '/dashboard.php', 'page', NULL, '192.168.1.102', NULL, NULL, NULL, '2024-01-15 10:05:00'),
(5, 2, 'manager_john', 'delete', 'Deleted old report', '/reports/delete.php', 'report', 205, '192.168.1.101', NULL, NULL, NULL, '2024-01-15 11:40:00'),
(6, 4, 'finance_mike', 'download', 'Downloaded financial report', '/reports/download.php', 'report', 301, '192.168.1.103', NULL, NULL, NULL, '2024-01-15 12:25:00'),
(7, 1, 'admin_user', 'logout', 'User logged out', '/auth/logout.php', 'user', 1, '192.168.1.100', NULL, NULL, NULL, '2024-01-15 13:10:00'),
(8, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:13:11'),
(9, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:30'),
(10, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:32'),
(11, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:34'),
(12, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:36'),
(13, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:40'),
(14, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:42'),
(15, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:43'),
(16, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:46'),
(17, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:47'),
(18, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:49'),
(19, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:25:47'),
(20, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:25:49'),
(21, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:06'),
(22, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:06'),
(23, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:09'),
(24, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=view_employee&id=7', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:21'),
(25, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=get_department_managers&dept=Marketing', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:21'),
(26, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:30:14'),
(27, 6, 'lisa_marketing', 'login', 'User \'lisa_marketing\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:37'),
(28, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:37'),
(29, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:44'),
(30, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:55'),
(31, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:56'),
(32, 6, 'lisa_marketing', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:58'),
(33, 6, 'lisa_marketing', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:31:03'),
(34, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:31:08'),
(35, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:33:05'),
(36, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:33:50'),
(37, 6, 'lisa_marketing', 'logout', 'User \'lisa_marketing\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:34:41'),
(38, 6, 'lisa_marketing', 'login', 'User \'lisa_marketing\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:34:43'),
(39, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:34:43'),
(40, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:36:33'),
(41, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:36:48'),
(42, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:38:10'),
(43, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:23'),
(44, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:25'),
(45, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:51'),
(46, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:53'),
(47, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:55'),
(48, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:58'),
(49, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:13'),
(50, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:13'),
(51, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:27'),
(52, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:28'),
(53, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:28'),
(54, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:30'),
(55, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:32'),
(56, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:32'),
(57, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:33'),
(58, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:34'),
(59, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:34'),
(60, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:36'),
(61, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:37'),
(62, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:37'),
(63, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:38'),
(64, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:39'),
(65, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:39'),
(66, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:40'),
(67, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:42'),
(68, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:42'),
(69, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:43'),
(70, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:44'),
(71, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:19'),
(72, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:21'),
(73, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:21'),
(74, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:22'),
(75, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:22'),
(76, 6, 'lisa_marketing', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:24'),
(77, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:24'),
(78, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:20'),
(79, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:25'),
(80, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:28'),
(81, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:34'),
(82, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:37'),
(83, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:47'),
(84, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:19'),
(85, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:24'),
(86, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:26'),
(87, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:29'),
(88, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:46'),
(89, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:46'),
(90, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:48'),
(91, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:48'),
(92, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:51'),
(93, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:51'),
(94, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:53'),
(95, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:53'),
(96, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:56:26'),
(97, 6, 'lisa_marketing', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:56:28'),
(98, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:56:31'),
(99, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:44'),
(100, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:47'),
(101, 6, 'lisa_marketing', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:49'),
(102, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:52'),
(103, 6, 'lisa_marketing', 'logout', 'User \'lisa_marketing\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:54'),
(104, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:57:57'),
(105, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:57:57'),
(106, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:58:58'),
(107, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:59:03'),
(108, 3, 'john_manager', 'login', 'User \'john_manager\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:30'),
(109, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:30'),
(110, 3, 'john_manager', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:34'),
(111, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:37'),
(112, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:38'),
(113, 3, 'john_manager', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:39'),
(114, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:02:17'),
(115, 3, 'john_manager', 'logout', 'User \'john_manager\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:02:19'),
(116, 8, 'jane_hr', 'login', 'User \'jane_hr\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:26'),
(117, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:26'),
(118, 8, 'jane_hr', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:28'),
(119, 8, 'jane_hr', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:31'),
(120, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:34'),
(121, 8, 'jane_hr', 'logout', 'User \'jane_hr\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:03:16'),
(122, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:03:42'),
(123, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:03:42'),
(124, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:18'),
(125, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:23'),
(126, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:25'),
(127, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:31'),
(128, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:34'),
(129, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:13:47'),
(130, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:13:47'),
(131, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:19:34'),
(132, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:19:40'),
(133, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:19:40'),
(134, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:06'),
(135, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:14'),
(136, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:20'),
(137, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:28'),
(138, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:45'),
(139, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:21:55'),
(140, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:21:57'),
(141, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:00'),
(142, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:40'),
(143, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:48'),
(144, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:58'),
(145, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:01'),
(146, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:02'),
(147, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:07'),
(148, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:10'),
(149, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:13'),
(150, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:14'),
(151, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:16'),
(152, 8, 'jane_hr', 'login', 'User \'jane_hr\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:20'),
(153, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:20'),
(154, 8, 'jane_hr', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:21'),
(155, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:24'),
(156, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:48:21'),
(157, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:48:22'),
(158, 8, 'jane_hr', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:49:32'),
(159, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:49:35'),
(160, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:24'),
(161, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:26'),
(162, 8, 'jane_hr', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:33'),
(163, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:36'),
(164, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:38'),
(165, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:59:01'),
(166, 8, 'jane_hr', 'logout', 'User \'jane_hr\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:59:40'),
(167, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 08:59:44'),
(168, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 08:59:44');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(169, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 09:02:33'),
(170, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 09:02:40'),
(171, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'acd47bc116e261c3b1db309c5a4d47de', NULL, '2025-09-12 09:38:37'),
(172, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'acd47bc116e261c3b1db309c5a4d47de', NULL, '2025-09-12 09:38:38'),
(173, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'acd47bc116e261c3b1db309c5a4d47de', NULL, '2025-09-12 09:38:57'),
(174, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 09:39:05'),
(175, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 09:39:05'),
(176, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?action=new_employee', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:34:19'),
(177, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:35:49'),
(178, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:35:51'),
(179, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:36:33'),
(180, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:36:35'),
(181, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:36:59'),
(182, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=9', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:57:08'),
(183, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:57:19'),
(184, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=5', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:57:32'),
(185, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:05'),
(186, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:05'),
(187, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?action=new_project', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:22'),
(188, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=projects', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:33'),
(189, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:47'),
(190, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:21'),
(191, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:24'),
(192, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:27'),
(193, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:28'),
(194, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:28'),
(195, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?action=new_campaign', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:32'),
(196, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:35'),
(197, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:48'),
(198, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:58'),
(199, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:00:36'),
(200, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:00:46'),
(201, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:00:59'),
(202, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:05'),
(203, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:13'),
(204, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:18'),
(205, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:22'),
(206, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:02:07'),
(207, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:03:32'),
(208, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php?action=new_client', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:03:35'),
(209, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php?action=new_client', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:04:46'),
(210, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:07'),
(211, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:07'),
(212, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:05:11'),
(213, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:20'),
(214, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:58'),
(215, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:06:12'),
(216, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php?action=new_client', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:06:21'),
(217, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:06:56'),
(218, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:38'),
(219, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:40'),
(220, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:52'),
(221, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:52'),
(222, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:09:26'),
(223, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=view_employee&id=7', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:03'),
(224, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=get_department_managers&dept=Marketing', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:03'),
(225, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=view_employee&id=8', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:27'),
(226, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=get_department_managers&dept=Finance', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:27'),
(227, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:11:07'),
(228, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:25:53'),
(229, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:00'),
(230, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:09'),
(231, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:13'),
(232, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:16'),
(233, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:17'),
(234, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:19'),
(235, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:21'),
(236, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:23'),
(237, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:24'),
(238, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:31'),
(239, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:33'),
(240, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:30:01'),
(241, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:30:01'),
(242, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:31:11'),
(243, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:31:28'),
(244, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:33:32'),
(245, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:34:59'),
(246, 1, 'admin', 'create', 'Created performance review for employee ID 7', '/departments/hr.php', 'performance_review', 1, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', '{\"employee_id\":7,\"reviewer_id\":8,\"review_period_start\":\"\",\"review_period_end\":\"\",\"overall_rating\":3}', '2025-09-12 12:34:59'),
(247, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:38:19'),
(248, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:38:51'),
(249, 1, 'admin', 'create', 'Created performance review for employee ID 5', '/departments/hr.php', 'performance_review', 2, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', '{\"employee_id\":5,\"reviewer_id\":5,\"review_period_start\":\"2025-09-01\",\"review_period_end\":\"2025-09-12\",\"overall_rating\":3}', '2025-09-12 12:38:51'),
(250, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:39:45'),
(251, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:41:35'),
(252, 1, 'admin', 'create', 'Created personal leave request for 14 days', '/departments/hr.php', 'leave_request', 19, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', '{\"employee_id\":5,\"leave_type\":\"personal\",\"start_date\":\"2025-09-12\",\"end_date\":\"2025-09-25\",\"days_requested\":14}', '2025-09-12 12:41:35'),
(253, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:18'),
(254, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:22'),
(255, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:25'),
(256, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:26'),
(257, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:38'),
(258, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:40'),
(259, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:52'),
(260, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:58'),
(261, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:43:05'),
(262, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:43:17'),
(263, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:46:04'),
(264, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:46:33'),
(265, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:52:39'),
(266, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:52:40'),
(267, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:54:03'),
(268, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:16'),
(269, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:50'),
(270, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:52'),
(271, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:53'),
(272, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:11:57'),
(273, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:11:58'),
(274, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:12:06'),
(275, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:12:09'),
(276, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:44'),
(277, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:46'),
(278, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:53'),
(279, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:54'),
(280, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:16:36'),
(281, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:18:33'),
(282, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:18:34'),
(283, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:20:56'),
(284, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:20:57'),
(285, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:22:54'),
(286, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:22:55'),
(287, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:23:05'),
(288, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:23:07'),
(289, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:05'),
(290, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:06'),
(291, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:08'),
(292, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:17'),
(293, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:19'),
(294, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:01'),
(295, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:11'),
(296, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:27'),
(297, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:35'),
(298, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:37'),
(299, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:39'),
(300, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:41'),
(301, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:44'),
(302, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:45'),
(303, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:47'),
(304, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:49'),
(305, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:00'),
(306, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:01'),
(307, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:01'),
(308, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:07'),
(309, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:11'),
(310, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:17'),
(311, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:48:50'),
(312, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:48:51'),
(313, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:51:21'),
(314, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:51:23'),
(315, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:53:18'),
(316, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:00'),
(317, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:05'),
(318, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:07'),
(319, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:57'),
(320, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:56:44'),
(321, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:56:56'),
(322, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:58:38'),
(323, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:17'),
(324, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:19');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(325, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:20'),
(326, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:22'),
(327, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:23'),
(328, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:28'),
(329, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:00:48'),
(330, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:01:19'),
(331, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:01:45'),
(332, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:01:48'),
(333, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:02:29'),
(334, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:02:33'),
(335, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:39:52'),
(336, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:39:52'),
(337, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:41:04'),
(338, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:43:26'),
(339, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:44:07'),
(340, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:44:51'),
(341, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:44:58'),
(342, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:45:31'),
(343, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:45:33'),
(344, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:33'),
(345, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:33'),
(346, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:46'),
(347, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:49'),
(348, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:55'),
(349, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:11:10'),
(350, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:12:03'),
(351, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:13:18'),
(352, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:14:16'),
(353, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:14:47'),
(354, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:30'),
(355, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:36'),
(356, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:55'),
(357, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:57'),
(358, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:18:13'),
(359, 1, 'admin', 'edit', 'Approved leave request', '/departments/hr.php', 'leave_request', 9, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', '{\"new_status\":\"approved\",\"approved_by\":1}', '2025-09-19 18:18:13'),
(360, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:18:33'),
(361, 1, 'admin', 'edit', 'Rejected leave request', '/departments/hr.php', 'leave_request', 14, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', '{\"new_status\":\"rejected\",\"approved_by\":1}', '2025-09-19 18:18:33'),
(362, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 08:54:36'),
(363, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 08:54:36'),
(364, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:06:59'),
(365, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:07:50'),
(366, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:07:59'),
(367, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:08:39'),
(368, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:14:23'),
(369, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=projects', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:19:55'),
(370, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=create', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:19:59'),
(371, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=projects', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:24:57'),
(372, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:13'),
(373, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:13'),
(374, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:36'),
(375, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:41'),
(376, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:43'),
(377, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:27:39'),
(378, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:27:50'),
(379, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:27:52'),
(380, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:28:08'),
(381, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:28:16'),
(382, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:29:55'),
(383, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 10:04:08'),
(384, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:01:44'),
(385, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:02:07'),
(386, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:02:34'),
(387, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:03:19'),
(388, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:03:27'),
(389, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '197.184.74.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.122 Mobile/15E148 Safari/604.1', '26658a0152f04e66b071c19afeaca0dc', NULL, '2025-09-23 00:28:28'),
(390, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '197.184.74.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.122 Mobile/15E148 Safari/604.1', '26658a0152f04e66b071c19afeaca0dc', NULL, '2025-09-23 00:28:28'),
(391, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 14:33:36'),
(392, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 14:33:36'),
(393, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 14:33:54'),
(394, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 17:15:03'),
(395, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-15 09:38:00'),
(396, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:24'),
(397, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:24'),
(398, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:31'),
(399, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=11', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:47'),
(400, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:44:53'),
(401, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:44:55'),
(402, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:45:02'),
(403, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:26'),
(404, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:28'),
(405, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:30'),
(406, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:35'),
(407, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar&month=09&year=2025', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:39'),
(408, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar&month=08&year=2025', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:42'),
(409, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar&month=09&year=2025', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:45'),
(410, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:46'),
(411, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:55'),
(412, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:01:00'),
(413, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:01:00'),
(414, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:04:35'),
(415, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:04:49'),
(416, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-29 08:22:07'),
(417, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:18:47'),
(418, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:18:48'),
(419, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:17'),
(420, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:19'),
(421, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:24'),
(422, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:27'),
(423, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:28'),
(424, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:29'),
(425, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:32'),
(426, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:34'),
(427, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/development/Consulting-Hub%20(1)/Consulting-Hub/auth/login.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:31:29'),
(428, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:31:29'),
(429, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:31:36'),
(430, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:31:42'),
(431, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:31:43'),
(432, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:31:45'),
(433, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=5', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:32:20'),
(434, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:32:53'),
(435, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:32:55'),
(436, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:32:56'),
(437, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:32:58'),
(438, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:33:01'),
(439, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:33:02'),
(440, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:33:04'),
(441, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:33:06'),
(442, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:36:07'),
(443, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:36:15'),
(444, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:36:21'),
(445, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:36:30'),
(446, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:36:57'),
(447, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:37:42'),
(448, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:38:25'),
(449, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:38:29'),
(450, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:39:37'),
(451, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:39:43'),
(452, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:40:05'),
(453, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:40:31'),
(454, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:41:04'),
(455, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:41:22'),
(456, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:41:36'),
(457, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:42:44'),
(458, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:43:02'),
(459, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:43:12'),
(460, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:00'),
(461, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:23'),
(462, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:51'),
(463, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:52'),
(464, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:53'),
(465, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:54'),
(466, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:55'),
(467, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:44:58'),
(468, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:40'),
(469, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:46'),
(470, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:48'),
(471, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:49'),
(472, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:50'),
(473, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:51'),
(474, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:51'),
(475, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:45:52'),
(476, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:52:51'),
(477, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:22'),
(478, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:26'),
(479, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:29'),
(480, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:30'),
(481, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:32');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(482, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:33'),
(483, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:53:35'),
(484, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-01 19:59:25'),
(485, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:19'),
(486, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:21'),
(487, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:23'),
(488, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:24'),
(489, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:25'),
(490, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:26'),
(491, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:27'),
(492, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=projects', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:28'),
(493, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 3)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:34'),
(494, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:48'),
(495, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 09:25:58'),
(496, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:38'),
(497, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:43'),
(498, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:45'),
(499, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:48'),
(500, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:49'),
(501, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:51'),
(502, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:52'),
(503, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:53'),
(504, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:56'),
(505, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:11:57'),
(506, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:12:08'),
(507, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:12:51'),
(508, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:12:52'),
(509, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?action=new_lead', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:15:07'),
(510, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?action=new_lead', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:15:09'),
(511, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:15:17'),
(512, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:15:28'),
(513, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:15:33'),
(514, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:30'),
(515, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:31'),
(516, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:33'),
(517, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=projects', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:34'),
(518, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:36'),
(519, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=projects', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:37'),
(520, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:38'),
(521, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:39'),
(522, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:39'),
(523, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:40'),
(524, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:41'),
(525, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:42'),
(526, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:43'),
(527, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:16:44'),
(528, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:05'),
(529, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:06'),
(530, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:22'),
(531, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:24'),
(532, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:44'),
(533, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:46'),
(534, 1, 'admin', 'page_visit', 'User accessed BD dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:21:50'),
(535, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:50:35'),
(536, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:50:38'),
(537, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:50:40'),
(538, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:50:42'),
(539, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:50:53'),
(540, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:51:51'),
(541, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:56:35'),
(542, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:56:38'),
(543, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:56:43'),
(544, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:56:46'),
(545, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:16'),
(546, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:23'),
(547, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:32'),
(548, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:32'),
(549, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:33'),
(550, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:33'),
(551, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:33'),
(552, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:33'),
(553, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:33'),
(554, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:34'),
(555, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:57:36'),
(556, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:58:04'),
(557, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:58:09'),
(558, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:58:14'),
(559, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:58:19'),
(560, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:58:23'),
(561, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:58:25'),
(562, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 10:59:41'),
(563, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:00:39'),
(564, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:00:41'),
(565, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:00:47'),
(566, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:01:18'),
(567, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:01:42'),
(568, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:05'),
(569, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:09'),
(570, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:15'),
(571, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:42'),
(572, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:51'),
(573, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:52'),
(574, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:53'),
(575, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:54'),
(576, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:55'),
(577, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:56'),
(578, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:02:58'),
(579, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:03:12'),
(580, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:03:47'),
(581, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:02'),
(582, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:27'),
(583, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:31'),
(584, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:32'),
(585, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:33'),
(586, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:34'),
(587, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:35'),
(588, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:37'),
(589, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:04:45'),
(590, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:07'),
(591, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:12'),
(592, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:15'),
(593, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:18'),
(594, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:24'),
(595, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:29'),
(596, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:31'),
(597, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:44'),
(598, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:51'),
(599, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:18:56'),
(600, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:32:17'),
(601, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:32:28'),
(602, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:32:36'),
(603, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:32:39'),
(604, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:32:42'),
(605, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:32:43'),
(606, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:33:08'),
(607, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:33:12'),
(608, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:38'),
(609, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:41'),
(610, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:43'),
(611, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:43'),
(612, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:44'),
(613, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:45'),
(614, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:47'),
(615, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:48'),
(616, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:49'),
(617, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:53'),
(618, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:55'),
(619, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:37:56'),
(620, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:38:01'),
(621, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:38:03'),
(622, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:38:03'),
(623, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:38:25'),
(624, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:38:58'),
(625, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:38:59'),
(626, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:39:00'),
(627, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:39:01'),
(628, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:39:56'),
(629, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:40:35'),
(630, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:44:37'),
(631, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:44:42'),
(632, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:45:31'),
(633, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:45:43');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(634, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:46:07'),
(635, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:46:14'),
(636, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:46:20'),
(637, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:46:26'),
(638, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 11:46:31'),
(639, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:01:25'),
(640, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:01:35'),
(641, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:01:44'),
(642, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:01:50'),
(643, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:01:56'),
(644, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:02:06'),
(645, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:02:23'),
(646, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:02:27'),
(647, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:02:37'),
(648, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:21'),
(649, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:27'),
(650, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:28'),
(651, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:30'),
(652, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:31'),
(653, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:34'),
(654, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:34'),
(655, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:04:37'),
(656, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:05:47'),
(657, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 5)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=5', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:07:14'),
(658, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 5)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=5', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:08:04'),
(659, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:08:18'),
(660, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:08:50'),
(661, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:08:52'),
(662, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:24'),
(663, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads&success=lead_created', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:24'),
(664, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks&lead_id=1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:30'),
(665, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:36'),
(666, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:46'),
(667, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:47'),
(668, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:53'),
(669, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:54'),
(670, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:54'),
(671, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:14:55'),
(672, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:04'),
(673, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:07'),
(674, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:09'),
(675, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:10'),
(676, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:42'),
(677, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:44'),
(678, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:45'),
(679, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:15:50'),
(680, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:37'),
(681, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks&success=task_created', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:37'),
(682, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:38'),
(683, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:41'),
(684, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:43'),
(685, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:46'),
(686, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:46'),
(687, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:47'),
(688, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:50'),
(689, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:16:53'),
(690, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:17:30'),
(691, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities&lead_id=1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:17:32'),
(692, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities&lead_id=1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:10'),
(693, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities&success=activity_logged', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:10'),
(694, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:13'),
(695, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:24'),
(696, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:44'),
(697, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:48'),
(698, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:52'),
(699, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:19:58'),
(700, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:20:14'),
(701, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:20:16'),
(702, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:20:20'),
(703, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:20:24'),
(704, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:20:28'),
(705, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:20:45'),
(706, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:34:47'),
(707, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:34:51'),
(708, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:34:59'),
(709, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:35:14'),
(710, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:35:17'),
(711, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:35:23'),
(712, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:40:20'),
(713, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:41:08'),
(714, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:41:10'),
(715, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:41:29'),
(716, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:41:37'),
(717, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:42:33'),
(718, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities&success=activity_logged', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:42:33'),
(719, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:42:38'),
(720, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:42:44'),
(721, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:43:00'),
(722, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:43:04'),
(723, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:45:23'),
(724, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:45:53'),
(725, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:46:00'),
(726, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:46:02'),
(727, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:46:33'),
(728, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:21'),
(729, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:22'),
(730, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:25'),
(731, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:27'),
(732, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:28'),
(733, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:28'),
(734, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:29'),
(735, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:31'),
(736, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets&success=targets_updated', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:31'),
(737, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets&success=targets_updated', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:50'),
(738, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:52'),
(739, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:49:53'),
(740, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:52:43'),
(741, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:52:45'),
(742, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:52:47'),
(743, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:52:48'),
(744, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:52:49'),
(745, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:52:51'),
(746, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:53:00'),
(747, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:53:27'),
(748, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:53:34'),
(749, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks&success=task_completed', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:53:34'),
(750, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:53:37'),
(751, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:53:51'),
(752, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:54:00'),
(753, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:54:05'),
(754, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:54:12'),
(755, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:26'),
(756, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:27'),
(757, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:28'),
(758, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:29'),
(759, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:30'),
(760, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:39'),
(761, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 12:55:40'),
(762, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:03:07'),
(763, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:05:25'),
(764, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:13:26'),
(765, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?action=new_lead', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:14:22'),
(766, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:14:26'),
(767, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php?action=new_client', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:14:27'),
(768, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:14:29'),
(769, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:14:33'),
(770, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:14:37'),
(771, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:20:43'),
(772, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:24'),
(773, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:29'),
(774, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:33'),
(775, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:33'),
(776, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?action=new_invoice', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:46'),
(777, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:50'),
(778, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:22:52'),
(779, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:24:47'),
(780, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:25:43'),
(781, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:25:56'),
(782, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:32'),
(783, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:41');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(784, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:45'),
(785, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:45'),
(786, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:46'),
(787, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:47'),
(788, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:48'),
(789, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:49'),
(790, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 13:26:53'),
(791, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:32'),
(792, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:34'),
(793, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:36'),
(794, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:38'),
(795, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:39'),
(796, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:40'),
(797, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:41'),
(798, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:42'),
(799, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:43'),
(800, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:44'),
(801, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:45'),
(802, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:47'),
(803, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:48'),
(804, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:50'),
(805, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:18:51'),
(806, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:25'),
(807, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:26'),
(808, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:27'),
(809, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:28'),
(810, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:29'),
(811, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:30'),
(812, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:30'),
(813, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:31'),
(814, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:32'),
(815, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:33'),
(816, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:19:34'),
(817, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:12'),
(818, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:12'),
(819, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:13'),
(820, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:16'),
(821, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:17'),
(822, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:18'),
(823, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:19'),
(824, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:20'),
(825, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:22'),
(826, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:24'),
(827, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:21:44'),
(828, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:41'),
(829, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:43'),
(830, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:43'),
(831, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:44'),
(832, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:44'),
(833, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:45'),
(834, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:46'),
(835, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:47'),
(836, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:50'),
(837, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:52'),
(838, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:52'),
(839, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:53'),
(840, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:54'),
(841, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:55'),
(842, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:56'),
(843, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:58'),
(844, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:23:59'),
(845, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:24:00'),
(846, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:24:00'),
(847, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:24:04'),
(848, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:36'),
(849, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:39'),
(850, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:41'),
(851, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:42'),
(852, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:43'),
(853, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:45'),
(854, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:47'),
(855, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:26:47'),
(856, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:24'),
(857, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:26'),
(858, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:27'),
(859, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:28'),
(860, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:30'),
(861, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:31'),
(862, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:28:32'),
(863, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:35:21'),
(864, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:35:25'),
(865, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:35:26'),
(866, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:04'),
(867, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:05'),
(868, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:05'),
(869, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:05'),
(870, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:08'),
(871, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:10'),
(872, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:11'),
(873, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:12'),
(874, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:13'),
(875, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:18'),
(876, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:20'),
(877, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub%20(1)/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:21'),
(878, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:30'),
(879, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:32'),
(880, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:32'),
(881, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:34'),
(882, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:35'),
(883, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:35'),
(884, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:36'),
(885, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:38'),
(886, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:39'),
(887, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:41'),
(888, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:43'),
(889, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:45'),
(890, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:45'),
(891, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:47'),
(892, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:48'),
(893, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:49'),
(894, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:51'),
(895, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:54'),
(896, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:55'),
(897, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:56'),
(898, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:36:59'),
(899, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:37:00'),
(900, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:37:01'),
(901, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:37:04'),
(902, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:37:08'),
(903, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:38:35'),
(904, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:38:46'),
(905, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:41:34'),
(906, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:41:38'),
(907, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:55:38'),
(908, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:57:11'),
(909, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:59:01'),
(910, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 14:59:09'),
(911, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:02:41'),
(912, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:03:21'),
(913, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:04:45'),
(914, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:04:48'),
(915, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:04:52'),
(916, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:04:53'),
(917, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:07'),
(918, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:12'),
(919, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php?view=projects', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:32'),
(920, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:37'),
(921, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:37'),
(922, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:49'),
(923, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:53'),
(924, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/project_detail.php?id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:53'),
(925, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:05:56'),
(926, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:13:09'),
(927, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:13:27'),
(928, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:14:19'),
(929, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:30:55'),
(930, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:30:58'),
(931, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:31:01'),
(932, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:31:03'),
(933, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:31:04'),
(934, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:31:06');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(935, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:31:09'),
(936, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:36:30'),
(937, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:37:14'),
(938, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:39:27'),
(939, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:40:11'),
(940, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:41:18'),
(941, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:41:22'),
(942, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:41:25'),
(943, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:41:42'),
(944, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:43'),
(945, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:44'),
(946, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:45'),
(947, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:46'),
(948, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:47'),
(949, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:52'),
(950, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:53'),
(951, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:55'),
(952, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:56'),
(953, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:42:57'),
(954, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:04'),
(955, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:37'),
(956, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:38'),
(957, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:39'),
(958, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:41'),
(959, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:44'),
(960, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:54'),
(961, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:56'),
(962, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:43:57'),
(963, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:44:00'),
(964, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:44:05'),
(965, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:44:08'),
(966, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:45:59'),
(967, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:50:42'),
(968, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php?ajax=view_employee&id=7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:50:56'),
(969, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php?ajax=get_department_managers&dept=Marketing', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:50:56'),
(970, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php?ajax=view_employee&id=7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:50:58'),
(971, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:53:13'),
(972, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:54:54'),
(973, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:56:32'),
(974, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:12'),
(975, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:13'),
(976, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=leads', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:14'),
(977, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=activities', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:15'),
(978, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:17'),
(979, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:19'),
(980, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=tasks', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:20'),
(981, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=targets', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:31'),
(982, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/bd.php?view=reports', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:32'),
(983, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:35'),
(984, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:39'),
(985, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:41'),
(986, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:42'),
(987, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:44'),
(988, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:44'),
(989, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:45'),
(990, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:46'),
(991, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:47'),
(992, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:48'),
(993, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 15:57:53'),
(994, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:14:19'),
(995, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:05'),
(996, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:13'),
(997, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:15'),
(998, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:20'),
(999, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:26'),
(1000, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:31'),
(1001, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:20:37'),
(1002, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:07'),
(1003, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:10'),
(1004, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:12'),
(1005, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:14'),
(1006, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:16'),
(1007, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:17'),
(1008, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:19'),
(1009, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:20'),
(1010, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:26'),
(1011, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:28'),
(1012, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:29'),
(1013, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:30'),
(1014, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:32'),
(1015, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:33'),
(1016, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:35'),
(1017, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:36'),
(1018, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:38'),
(1019, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:21:43'),
(1020, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:00'),
(1021, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:07'),
(1022, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:09'),
(1023, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:13'),
(1024, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:14'),
(1025, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:15'),
(1026, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:21'),
(1027, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:28'),
(1028, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:31'),
(1029, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:33'),
(1030, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:22:49'),
(1031, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:23:13'),
(1032, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:30:09'),
(1033, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:30:09'),
(1034, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:30:10'),
(1035, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:30:12'),
(1036, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub%20(1)/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 16:30:15'),
(1037, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:24'),
(1038, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:26'),
(1039, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:27'),
(1040, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:28'),
(1041, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:28'),
(1042, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:29'),
(1043, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:29'),
(1044, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:30'),
(1045, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:31'),
(1046, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 17:36:31'),
(1047, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:08:21'),
(1048, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:37:52'),
(1049, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:38:32'),
(1050, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:38:32'),
(1051, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:38:33'),
(1052, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:38:34'),
(1053, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:38:35'),
(1054, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 19:38:36'),
(1055, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:43'),
(1056, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:44'),
(1057, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:45'),
(1058, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:46'),
(1059, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:47'),
(1060, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:47'),
(1061, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:48'),
(1062, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:49'),
(1063, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:50'),
(1064, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:51'),
(1065, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:51'),
(1066, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:51'),
(1067, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:51'),
(1068, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:52'),
(1069, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:52'),
(1070, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:52'),
(1071, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:52'),
(1072, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:52'),
(1073, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:53'),
(1074, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'so9vjqgr704n02vuj60fetrlin', NULL, '2025-11-02 20:18:53'),
(1075, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/development/Consulting-Hub/auth/login.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:12:56'),
(1076, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:12:56'),
(1077, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:12:59'),
(1078, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:01'),
(1079, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:02'),
(1080, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:02'),
(1081, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:03');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(1082, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:04'),
(1083, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:05'),
(1084, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:12'),
(1085, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:13'),
(1086, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=email-campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:14'),
(1087, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=blog-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:16'),
(1088, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=campaigns', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:17'),
(1089, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=overview', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:13:51'),
(1090, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:27:26'),
(1091, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:27:26'),
(1092, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:27:27'),
(1093, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:27:27'),
(1094, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'mkrr9kg4tnr2tvio01g3cvvc95', NULL, '2025-11-04 06:27:28'),
(1095, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/development/Consulting-Hub/auth/login.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:19:09'),
(1096, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:19:09'),
(1097, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:20:15'),
(1098, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:20:29'),
(1099, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:20:33'),
(1100, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:20:49'),
(1101, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:25:51'),
(1102, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:25:54'),
(1103, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:26:22'),
(1104, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:27:23'),
(1105, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:28:18'),
(1106, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:29:54'),
(1107, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:35:46'),
(1108, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:35:48'),
(1109, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:35:53'),
(1110, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:35:56'),
(1111, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:35:59'),
(1112, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:36:19'),
(1113, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:36:35'),
(1114, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:10'),
(1115, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:13'),
(1116, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:18'),
(1117, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:20'),
(1118, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:23'),
(1119, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:24'),
(1120, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:25'),
(1121, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:26'),
(1122, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:27'),
(1123, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:29'),
(1124, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:30'),
(1125, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:32'),
(1126, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 10:59:33'),
(1127, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:09'),
(1128, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:24'),
(1129, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:34'),
(1130, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:35'),
(1131, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:36'),
(1132, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:37'),
(1133, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:38'),
(1134, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:40'),
(1135, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:40'),
(1136, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:42'),
(1137, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:00:43'),
(1138, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:01'),
(1139, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:02'),
(1140, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:03'),
(1141, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:04'),
(1142, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:05'),
(1143, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:06'),
(1144, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:07'),
(1145, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:08'),
(1146, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:09'),
(1147, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:10'),
(1148, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:11'),
(1149, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/development/Consulting-Hub/departments/insights.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:13'),
(1150, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:14'),
(1151, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:01:15'),
(1152, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:04:59'),
(1153, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:00'),
(1154, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:01'),
(1155, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:02'),
(1156, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:03'),
(1157, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:03'),
(1158, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:04'),
(1159, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:05'),
(1160, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:06'),
(1161, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:07'),
(1162, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:07'),
(1163, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:09'),
(1164, 1, 'admin', 'page_visit', 'Visited Bd page', '/development/Consulting-Hub/departments/bd.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:10'),
(1165, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:10'),
(1166, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:11'),
(1167, 1, 'admin', 'page_visit', 'Visited It page', '/development/Consulting-Hub/departments/it.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:11'),
(1168, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:16'),
(1169, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-calendar', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:25'),
(1170, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/development/Consulting-Hub/departments/marketing.php?view=social-posts', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:05:28'),
(1171, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:06:11'),
(1172, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'a0de3uh0kdod0ku4d7diekncop', NULL, '2025-11-04 11:06:13'),
(1173, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/development/Consulting-Hub/auth/login.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'lrmgbv4pk60b5e3mcnua275n0e', NULL, '2026-02-11 18:05:06'),
(1174, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'lrmgbv4pk60b5e3mcnua275n0e', NULL, '2026-02-11 18:05:06'),
(1175, 1, 'admin', 'page_visit', 'Visited HR Department page', '/development/Consulting-Hub/departments/hr.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'lrmgbv4pk60b5e3mcnua275n0e', NULL, '2026-02-11 18:05:18'),
(1176, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/development/Consulting-Hub/auth/login.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:42:47'),
(1177, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:42:48'),
(1178, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:42:53'),
(1179, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:44:41'),
(1180, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:44:53'),
(1181, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:46:02'),
(1182, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:46:42'),
(1183, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:46:48'),
(1184, 1, 'admin', 'logout', 'User \'admin\' logged out', '/development/Consulting-Hub/auth/logout.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'aodqktt58567djouv20fscv3at', NULL, '2026-02-27 07:46:56'),
(1185, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/development/Consulting-Hub/auth/login.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:03:10'),
(1186, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/development/Consulting-Hub/dashboard.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:03:10'),
(1187, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:03:15'),
(1188, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:03:21'),
(1189, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:04:13'),
(1190, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/development/Consulting-Hub/departments/finance.php?ajax=view_invoice&id=6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:04:18'),
(1191, 1, 'admin', 'page_visit', 'Viewed clients management page', '/development/Consulting-Hub/departments/clients.php', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'hepq2vjfrddbn3f2g8rv7tstqq', NULL, '2026-02-27 09:07:11');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_education`
--
ALTER TABLE `candidate_education`
  ADD CONSTRAINT `candidate_education_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_work_experience`
--
ALTER TABLE `candidate_work_experience`
  ADD CONSTRAINT `candidate_work_experience_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD CONSTRAINT `client_contacts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_contact_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_meetings`
--
ALTER TABLE `client_meetings`
  ADD CONSTRAINT `client_meetings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_meetings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD CONSTRAINT `fk_consultation_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `custom_reports`
--
ALTER TABLE `custom_reports`
  ADD CONSTRAINT `custom_reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD CONSTRAINT `email_campaigns_ibfk_1` FOREIGN KEY (`marketing_campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_campaigns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_email_campaign_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_recipients`
--
ALTER TABLE `email_recipients`
  ADD CONSTRAINT `email_recipients_ibfk_1` FOREIGN KEY (`email_campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD CONSTRAINT `hr_employees_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD CONSTRAINT `hr_leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `marketing_blog_posts`
--
ALTER TABLE `marketing_blog_posts`
  ADD CONSTRAINT `marketing_blog_posts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketing_blog_posts_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketing_blog_posts_ibfk_3` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD CONSTRAINT `fk_campaign_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketing_campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `money_flow`
--
ALTER TABLE `money_flow`
  ADD CONSTRAINT `money_flow_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `money_flow_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `money_flow_ibfk_3` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `money_flow_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `project_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `project_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_revenues`
--
ALTER TABLE `project_revenues`
  ADD CONSTRAINT `project_revenues_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_revenues_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_revenues_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_media_posts`
--
ALTER TABLE `social_media_posts`
  ADD CONSTRAINT `fk_social_post_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `social_media_posts_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `social_media_posts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_activity`
--
ALTER TABLE `system_activity`
  ADD CONSTRAINT `system_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
