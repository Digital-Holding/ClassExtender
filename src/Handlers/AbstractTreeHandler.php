<?php

declare(strict_types=1);

namespace ClassExtender\Handlers;

use PhpParser\Comment\Doc;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\TraitUse;
use Traitor\Handlers\AbstractTreeHandler as BaseAbstractTreeHandler;

class AbstractTreeHandler extends BaseAbstractTreeHandler implements AbstractTreeHandlerInterface
{
    /** @var string */
    protected $prefix;

    public function __construct($content, $trait, $class)
    {
        $namespace = explode('\\', $trait, 3);
        $this->prefix = $namespace[0] . $namespace[1];

        parent::__construct($content, $trait, $class);
    }

    /**
     * @return $this
     */
    public function handleRemove()
    {
        $this->buildSyntaxTree()
            ->removeTraitImport()
            ->buildSyntaxTree()
            ->removeTraiUseStatement();

        return $this;
    }

    /**
     * @return $this
     */
    public function handleInterface()
    {
        $this->buildInterfaceSyntaxTree()
            ->addTraitImport()
            ->buildInterfaceSyntaxTree()
            ->addExtendedInterface();

        return $this;
    }

    /**
     * @return $this
     */
    public function handleRemoveInterface()
    {
        $this->buildInterfaceSyntaxTree()
            ->removeTraitImport()
            ->buildInterfaceSyntaxTree()
            ->removeExtendedImportStatement();

        return $this;
    }

    /**
     * @return \Traitor\Handlers\AbstractTreeHandler
     * @throws \Exception
     *
     */
    protected function buildInterfaceSyntaxTree()
    {
        $this->parseContent()
            ->retrieveNamespace()
            ->retrieveImports()
            ->retrieveInterface()
            ->findClassDefinition();

        return $this;
    }

    /**
     * @return $this
     */
    protected function addTraitImport()
    {
        if ($this->hasTraitImport()) {
            return $this;
        }

        $startLine = null;
        $endLine = null;

        $lastImport = $this->getLastImport();

        if ($lastImport === false) {
            $lineNumber = $this->classAbstractTree->getLine() - 1;
            $newImport = 'use ' . $this->trait . ' as ' . $this->prefix . $this->traitShortName . ';' . $this->lineEnding;

            $startLine = $lineNumber;

            if ($this->hasClassDocBlock()) {
                /** @var array $docBlocks */
                $docBlocks = $this->retrieveClassDocBlocks();

                $startLine = $docBlocks[0]->getStartLine();
                $endLine = $docBlocks[0]->getEndLine();
            }

            array_splice($this->content, $startLine - 1, 0, $this->lineEnding);
        } else {
            $lineNumber = $this->getLastImport()->getAttribute('endLine');
            $newImport = 'use ' . $this->trait . ' as ' . $this->prefix . $this->traitShortName . ';' . $this->lineEnding;
        }

        array_splice($this->content, $lineNumber, 0, $newImport);

        return $this;
    }

    /**
     * @return $this
     */
    protected function removeTraitImport()
    {
        if (!$this->hasTraitImport()) {
            return $this;
        }

        foreach ($this->importStatements as $statement) {
            if ($statement->uses[0]->name->toString() == $this->trait) {
                unset($this->content[$statement->getLine() - 1]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function addTraitUseStatement()
    {
        if ($this->alreadyUsesTrait()) {
            return $this;
        }

        $line = $this->getNewTraitUseLine();

        $newTraitUse = static::getIndentation($this->content[$line]) . 'use ' . $this->prefix . $this->traitShortName . ';' . $this->lineEnding;

        array_splice($this->content, $line, 0, $newTraitUse);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addExtendedInterface()
    {
        $line = $this->getInterfaceLine();
        $lastExtendedInterfaceLine = $this->getLastExtendedInterfaceLine();

        if ($this->alreadyExtendsInterface()) {
            return $this;
        }

        $newInterfaceExtend = substr($this->content[$line], 0, -1) . ' extends ' . $this->prefix . $this->traitShortName . "\n";
        $interfaceLineLength = strlen($this->content[$line]);
        $newCommaSeparator = substr_replace($this->content[$line], ',', $interfaceLineLength - 1, 0);

        if (false !== strpos($this->content[$line], 'extends')) {
            $newInterfaceExtend = $this->prefix . $this->traitShortName . "\n";

            if (false !== strpos($this->content[$line + 1], '{')) {
                array_splice($this->content, $lastExtendedInterfaceLine, 0, $newCommaSeparator);
                array_splice($this->content, $lastExtendedInterfaceLine + 1, 0, $newInterfaceExtend);
                unset($this->content[$line]);
            } else {
                $interfaceLineLength = strlen($this->content[$lastExtendedInterfaceLine - 1]);
                $newCommaSeparator = substr_replace($this->content[$lastExtendedInterfaceLine - 1], ',', $interfaceLineLength - 1, 0);

                array_splice($this->content, $lastExtendedInterfaceLine, 0, $newCommaSeparator);
                array_splice($this->content, $lastExtendedInterfaceLine + 1, 0, $newInterfaceExtend);
                unset($this->content[$lastExtendedInterfaceLine - 1]);
            }

            return $this;
        }

        array_splice($this->content, $line + 1, 0, $newInterfaceExtend);
        unset($this->content[$line]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function removeTraiUseStatement()
    {
        if (!$this->alreadyUsesTrait()) {
            return $this;
        }

        $traitUses = array_filter($this->classAbstractTree->stmts, function ($statement) {
            return $statement instanceof TraitUse;
        });

        /** @var TraitUse $statement */
        foreach ($traitUses as $statement) {
            foreach ($statement->traits as $traitUse) {
                if ($traitUse->toString() == $this->trait
                    || $traitUse->toString() == $this->prefix . $this->traitShortName
                ) {
                    unset($this->content[$traitUse->getLine()]);
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function removeExtendedImportStatement()
    {
        $extendedImports = array_filter($this->classAbstractTree->extends, function ($statement) {
            return $statement instanceof Name;
        });

        if (!$this->alreadyExtendsInterface()) {
            return $this;
        }

        $interface = reset($this->classes);
        $numberOfExtends = count($interface->extends);
        $extendLines = [];

        foreach ($interface->extends as $extend) {
            array_push($extendLines, $extend->getAttributes());
        }

        /** @var Name $statement */
        foreach ($extendedImports as $statement) {
            foreach ($statement->parts as $extendImport) {
                if ($extendImport == $this->trait
                    || $extendImport == $this->prefix . $this->traitShortName
                ) {
                    $previousExtendImport = $this->content[$statement->getLine() - 1];

                    if (1 === count($extendedImports)) {
                        $interfaceWithoutExtendedInterface = str_replace($extendImport, "", $this->content[$statement->getLine()]);
                        $interfaceWithoutExtend = str_replace('extends', "", $interfaceWithoutExtendedInterface);

                        $this->content[$statement->getLine()] = trim($interfaceWithoutExtend) . "\n";
                    } else if (substr($previousExtendImport, -2) == ",\n") {
                        $newPreviousExtendImport = $this->content[$statement->getLine() - 1];

                        $previousExtendImportLine = array_search($statement->getLine() - 1, array_column($extendLines, 'startLine'));
                        $nexExtendImportLine = array_search($statement->getLine() + 1, array_column($extendLines, 'endLine'));

                        if (2 >= $numberOfExtends || ($previousExtendImportLine && !$nexExtendImportLine)) {
                            $newPreviousExtendImport = substr($this->content[$statement->getLine() - 1], 0, -2) . "\n";
                        }

                        unset($this->content[$statement->getLine()]);
                        unset($this->content[$statement->getLine() - 1]);

                        array_splice($this->content, $statement->getLine() - 2, 0, $newPreviousExtendImport);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     *
     */
    protected function retrieveNamespace()
    {
        $syntaxTree = $this->hasDeclare() ? $this->syntaxTree[1] : $this->syntaxTree[0];

        if (!isset($syntaxTree) || !($syntaxTree instanceof Namespace_)) {
            throw new \Exception("Could not locate namespace definition for class '" . $this->classShortName . "'");
        }

        $this->namespace = $syntaxTree;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasDeclare()
    {
        if ($this->syntaxTree[0] instanceof Declare_) {
            return true;
        }

        return false;
    }

    /**
     * @return $this
     */
    protected function retrieveInterface()
    {
        $this->classes = array_filter($this->namespace->stmts, function ($statement) {
            return $statement instanceof Interface_;
        });

        return $this;
    }

    /**
     * @return array
     */
    protected function retrieveClassDocBlocks()
    {
        $attributes = $this->classes[0]->getAttributes();

        /** @var array $docBlocks */
        $docBlocks = array_filter($attributes['comments'], function ($statement) {
            return $statement instanceof Doc;
        });

        return $docBlocks;
    }

    /**
     * @return bool
     */
    protected function hasClassDocBlock()
    {
        $attributes = $this->classes[0]->getAttributes();

        if (isset($attributes['comments'])) {
            /** @var array $docblock */
            $docBlocks = array_filter($attributes['comments'], function ($statement) {
                return $statement instanceof Doc;
            });

            return !empty($docBlocks);
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function alreadyUsesTrait()
    {
        $traitUses = array_filter($this->classAbstractTree->stmts, function ($statement) {
            return $statement instanceof TraitUse;
        });

        /** @var TraitUse $statement */
        foreach ($traitUses as $statement) {
            foreach ($statement->traits as $traitUse) {
                if ($traitUse->toString() == $this->trait
                    || $traitUse->toString() == $this->prefix . $this->traitShortName
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function alreadyExtendsInterface()
    {
        $extendedImports = array_filter($this->classAbstractTree->extends, function ($statement) {
            return $statement instanceof Name;
        });

        /** @var Name $statement */
        foreach ($extendedImports as $statement) {
            foreach ($statement->parts as $extendImport) {
                if ($extendImport == $this->trait
                    || $extendImport == $this->prefix . $this->traitShortName
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getInterfaceLine()
    {
        $interface = reset($this->classes);
        return $interface->getAttributes()['startLine'] - 1;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getLastExtendedInterfaceLine()
    {
        /** @var Interface_ $interface */
        $interface = reset($this->classes);

        if (true !== empty($interface->extends)) {
            /** @var Name $lastExtendedInterface */
            $lastExtendedInterface = end($interface->extends);

            return $lastExtendedInterface->getAttribute('startLine');
        }

        return $interface->getAttribute('startLine');
    }
}
