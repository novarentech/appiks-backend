<?php

namespace App\Services;

use App\Models\User;
use App\Models\Sharing;

class HeadlessDataGenerator
{
    /**
     * Fetch student's raw logs/venting entries and strip PII.
     *
     * @param User $student
     * @return string
     */
    public function generateSanitizedText(User $student): string
    {
        // Fetch all sharing logs for this student
        $sharings = Sharing::where('user_id', $student->id)->get();

        if ($sharings->isEmpty()) {
            return "No journaling or sharing history found for this student.";
        }

        // Gather all text content
        $combinedText = $sharings->map(function ($sharing) {
            return "Title: {$sharing->title}\nContent: {$sharing->description}";
        })->implode("\n\n---\n\n");

        // Define PII patterns to be stripped
        $piiTerms = array_filter([
            $student->name,
            $student->username,
            $student->phone,
            $student->identifier,
        ]);

        // Escape terms for safety in regex replacement
        $patterns = array_map(function ($term) {
            return '/' . preg_quote($term, '/') . '/i';
        }, $piiTerms);

        if (!empty($patterns)) {
            // Replace PII with placeholder
            $combinedText = preg_replace($patterns, '[REDACTED_PII]', $combinedText);
        }

        // Additional defensive regex for email addresses and phone numbers
        $combinedText = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[REDACTED_EMAIL]', $combinedText);
        $combinedText = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[REDACTED_PHONE]', $combinedText);

        return $combinedText;
    }
}
