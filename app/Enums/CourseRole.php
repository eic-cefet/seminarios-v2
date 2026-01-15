<?php

namespace App\Enums;

enum CourseRole: string
{
    case Aluno = 'Aluno';
    case Professor = 'Professor';
    case Outro = 'Outro';
}
