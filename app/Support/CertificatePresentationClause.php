<?php

namespace App\Support;

/**
 * Names the certificate's presentation type with the correct contracted "a"
 * article (e.g. "ao seminário", "à dissertação"). Thin façade over
 * PresentationTypeGrammar — the certificate line uses the "a" contraction
 * ("Compareceu, no dia X, {clause}"). Unknown / null / custom type names fall
 * back to the neutral "à apresentação".
 *
 * @see PresentationTypeGrammar for the gender/article source of truth.
 */
class CertificatePresentationClause
{
    public static function for(?string $typeName, bool $plural = false): string
    {
        return PresentationTypeGrammar::for($typeName, $plural)->withA();
    }
}
