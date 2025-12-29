-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 29. Dez 2025 um 18:30
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `anime_forum`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `beitraege`
--

CREATE TABLE `beitraege` (
  `beitrag_id` int(11) NOT NULL,
  `thema_id` int(11) NOT NULL,
  `benutzer_id` int(11) NOT NULL,
  `inhalt` text NOT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  `bearbeitet_am` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `benutzer`
--

CREATE TABLE `benutzer` (
  `benutzer_id` int(11) NOT NULL,
  `benutzername` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `passworthash` varchar(255) NOT NULL,
  `geburtsdatum` date NOT NULL,
  `rolle` enum('Nutzer','Admin') NOT NULL DEFAULT 'Nutzer',
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  `geloescht_am` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `benutzer`
--

INSERT INTO `benutzer` (`benutzer_id`, `benutzername`, `email`, `passworthash`, `geburtsdatum`, `rolle`, `erstellt_am`, `geloescht_am`) VALUES
(2, 'AB22', 'test.test@gmail.com', '$2y$10$4924TespyMBhLAPq6fZzouAtYaWq50LTBsc9TrW6GGFdDYpQBNkWy', '1999-02-12', 'Admin', '2025-12-04 20:58:58', NULL),
(3, 'AlexMANN', 'test.test1@gmail.com', '$2y$10$LNcj1d52JkJ3iyc.Dhle5uU6QfL8.EObWqGOcVRbQvZ.2q9sUgEcC', '1996-02-12', 'Nutzer', '2025-12-06 19:29:51', NULL),
(4, 'SakuraChan', 'test.test2@gmail.com', '$2y$10$SuSL3kQ8lw2syta4n9ZfJOKrOKq0N9KbsCQl0TCHfC2tEDNXxNIz2', '2005-01-12', 'Nutzer', '2025-12-11 13:33:03', '2025-12-11 21:56:03');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `kategorien`
--

CREATE TABLE `kategorien` (
  `kategorie_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `beschreibung` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `kategorien`
--

INSERT INTO `kategorien` (`kategorie_id`, `name`, `beschreibung`) VALUES
(2, 'Empfehlungen & Reviews', 'Hier tauschen Nutzer Empfehlungen und kurze Reviews aus.'),
(3, 'Serien- & Folgen-Diskussion', 'Diskussionen zu bestimmten Serien und einzelnen Folgen.'),
(4, 'Serien Linke', 'Hier findest du alle links um Anime zu sehen');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `themen`
--

CREATE TABLE `themen` (
  `thema_id` int(11) NOT NULL,
  `kategorie_id` int(11) NOT NULL,
  `benutzer_id` int(11) NOT NULL,
  `titel` varchar(150) NOT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `themen`
--

INSERT INTO `themen` (`thema_id`, `kategorie_id`, `benutzer_id`, `titel`, `erstellt_am`) VALUES
(7, 2, 3, 'Anime für Anfänger?', '2025-12-06 20:11:14'),
(8, 2, 3, 'Beste Anime?', '2025-12-06 21:37:37'),
(9, 2, 4, 'Einsteiger-Anime für Naruto-Fans?', '2025-12-11 13:34:33');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `beitraege`
--
ALTER TABLE `beitraege`
  ADD PRIMARY KEY (`beitrag_id`),
  ADD KEY `thema_id` (`thema_id`),
  ADD KEY `benutzer_id` (`benutzer_id`);

--
-- Indizes für die Tabelle `benutzer`
--
ALTER TABLE `benutzer`
  ADD PRIMARY KEY (`benutzer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indizes für die Tabelle `kategorien`
--
ALTER TABLE `kategorien`
  ADD PRIMARY KEY (`kategorie_id`);

--
-- Indizes für die Tabelle `themen`
--
ALTER TABLE `themen`
  ADD PRIMARY KEY (`thema_id`),
  ADD KEY `kategorie_id` (`kategorie_id`),
  ADD KEY `benutzer_id` (`benutzer_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `beitraege`
--
ALTER TABLE `beitraege`
  MODIFY `beitrag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `benutzer`
--
ALTER TABLE `benutzer`
  MODIFY `benutzer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT für Tabelle `kategorien`
--
ALTER TABLE `kategorien`
  MODIFY `kategorie_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT für Tabelle `themen`
--
ALTER TABLE `themen`
  MODIFY `thema_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `beitraege`
--
ALTER TABLE `beitraege`
  ADD CONSTRAINT `beitraege_ibfk_1` FOREIGN KEY (`thema_id`) REFERENCES `themen` (`thema_id`),
  ADD CONSTRAINT `beitraege_ibfk_2` FOREIGN KEY (`benutzer_id`) REFERENCES `benutzer` (`benutzer_id`);

--
-- Constraints der Tabelle `themen`
--
ALTER TABLE `themen`
  ADD CONSTRAINT `themen_ibfk_1` FOREIGN KEY (`kategorie_id`) REFERENCES `kategorien` (`kategorie_id`),
  ADD CONSTRAINT `themen_ibfk_2` FOREIGN KEY (`benutzer_id`) REFERENCES `benutzer` (`benutzer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
