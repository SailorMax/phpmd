<?php
/**
 * This file is part of PHP Mess Detector.
 *
 * Copyright (c) Manuel Pichler <mapi@phpmd.org>.
 * All rights reserved.
 *
 * Licensed under BSD License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Manuel Pichler <mapi@phpmd.org>
 * @copyright Manuel Pichler. All rights reserved.
 * @license https://opensource.org/licenses/bsd-license.php BSD License
 * @link http://phpmd.org/
 */

namespace PHPMD\Rule\CleanCode;

use PDepend\Source\AST\ASTAssignmentExpression;
use PDepend\Source\AST\ASTExpression;
use PDepend\Source\AST\ASTStatement;
use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Node\ASTNode;
use PHPMD\Rule\FunctionAware;
use PHPMD\Rule\MethodAware;

/**
 * If Statement Assignment Rule
 *
 * This rule covers the following cases:
 * - single assignment in an if clause
 * - multiple assignments in same if clause
 * - assignments in nested if clauses
 * - assignments in elseif clauses
 * - duplicated assignments (multiple conditions before and after *=* sign)
 *
 * Empty if clauses are skipped
 */
class IfStatementAssignment extends AbstractRule implements MethodAware, FunctionAware
{
    /**
     * This method checks if method/function has if clauses
     * that use assignment instead of comparison.
     *
     * @param AbstractNode $node An instance of MethodNode or FunctionNode class
     * @return void
     */
    public function apply(AbstractNode $node)
    {
        $statements = $this->getStatements($node);
        $expressions = $this->getExpressions($statements);
        $assignments = $this->getAssignments($expressions);

        $this->addViolations($node, $assignments);
    }

    /**
     * Extracts if and elseif statements from method/function body
     *
     * @param AbstractNode $node An instance of MethodNode or FunctionNode class
     * @return ASTStatement[]
     */
    private function getStatements(AbstractNode $node)
    {
        $ifStatements = $node->findChildrenOfType('IfStatement');
        $elseIfStatements = $node->findChildrenOfType('ElseIfStatement');

        return array_merge($ifStatements, $elseIfStatements);
    }

    /**
     * Extracts all expression from statements array
     *
     * @param ASTStatement[] $statements Array of if and elseif clauses
     * @return ASTExpression[]
     */
    private function getExpressions(array $statements)
    {
        $expressions = array();
        /** @var ASTNode $statement */
        foreach ($statements as $statement) {
            $expressions = array_merge($expressions, $statement->findChildrenOfType('Expression'));
        }

        return $expressions;
    }

    /**
     * Extracts all assignments from expressions array
     *
     * @param ASTExpression[] $expressions Array of expressions
     * @return ASTAssignmentExpression[]
     */
    private function getAssignments(array $expressions)
    {
        $assignments = array();
        /** @var ASTNode $expression */
        foreach ($expressions as $expression) {
            $assignments = array_merge($assignments, $expression->findChildrenOfType('AssignmentExpression'));
        }

        return $assignments;
    }

    /**
     * Signals if any violations have been found in given method or function
     *
     * @param AbstractNode $node An instance of MethodNode or FunctionNode class
     * @param ASTAssignmentExpression[] $assignments Array of assignments
     */
    private function addViolations(AbstractNode $node, array $assignments)
    {
        $processesViolations = array();
        /** @var \PDepend\Source\AST\AbstractASTNode $assignment */
        foreach ($assignments as $assignment) {
            if (null === $assignment || $assignment->getImage() !== '=') {
                continue;
            }

            $uniqueHash = $assignment->getStartColumn() . ':' . $assignment->getStartLine();
            if (!in_array($uniqueHash, $processesViolations)) {
                $processesViolations[] = $uniqueHash;
                $this->addViolation($node, array($assignment->getStartLine(), $assignment->getStartColumn()));
            }
        }
    }
}
