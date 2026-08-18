function Update-TargetRow {
    param(
        [xml]$Doc,
        [System.Xml.XmlNamespaceManager]$Ns,
        [string]$DescriptionMatch,
        [string]$ExpectedField,
        [string]$ExpectedType,
        [string]$NewLength,
        [string]$NewDescription
    )

    $rows = $Doc.SelectNodes('//w:tr', $Ns)
    $targetRow = $null

    foreach ($row in $rows) {
        $textNodes = $row.SelectNodes('.//w:t', $Ns)
        if ($textNodes.Count -lt 4) {
            continue
        }

        $values = @($textNodes | ForEach-Object { $_.InnerText.Trim() })
        if ($values -contains $DescriptionMatch) {
            $targetRow = $row
            break
        }
    }

    if ($null -eq $targetRow) {
        throw "Could not locate row for description: $DescriptionMatch"
    }

    $targetTexts = $targetRow.SelectNodes('.//w:t', $Ns)
    if ($targetTexts.Count -lt 4) {
        throw "Row for '$DescriptionMatch' does not have enough text cells"
    }

    if ($targetTexts[0].InnerText.Trim() -ne $ExpectedField) {
        throw "Field mismatch for '$DescriptionMatch'. Expected '$ExpectedField', found '$($targetTexts[0].InnerText.Trim())'"
    }

    if ($targetTexts[1].InnerText.Trim() -ne $ExpectedType) {
        throw "Type mismatch for '$DescriptionMatch'. Expected '$ExpectedType', found '$($targetTexts[1].InnerText.Trim())'"
    }

    $targetTexts[2].InnerText = $NewLength
    if ($NewDescription) {
        $targetTexts[3].InnerText = $NewDescription
    }
}

$origXmlPath = 'worddoc/_tmp_data_dictionary_orig_recheck/word/document.xml'
[xml]$doc = Get-Content -Raw $origXmlPath
$ns = New-Object System.Xml.XmlNamespaceManager($doc.NameTable)
$ns.AddNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main')

Update-TargetRow -Doc $doc -Ns $ns -DescriptionMatch 'Role assigned to the user: student, educ, psych, accountancy, teacher, admin, or superadmin' -ExpectedField 'role' -ExpectedType 'varchar' -NewLength '255' -NewDescription ''
Update-TargetRow -Doc $doc -Ns $ns -DescriptionMatch 'Role selected by the applicant during registration' -ExpectedField 'role' -ExpectedType 'varchar' -NewLength '255' -NewDescription 'Role selected by the applicant during registration (nullable in current schema)'
Update-TargetRow -Doc $doc -Ns $ns -DescriptionMatch 'Name or title of the class' -ExpectedField 'name' -ExpectedType 'varchar' -NewLength '100' -NewDescription ''
Update-TargetRow -Doc $doc -Ns $ns -DescriptionMatch 'Optional class code or section identifier, must be unique' -ExpectedField 'code' -ExpectedType 'varchar' -NewLength '20' -NewDescription ''
Update-TargetRow -Doc $doc -Ns $ns -DescriptionMatch 'Type of file attached to the module, such as pdf or mov' -ExpectedField 'file_type' -ExpectedType 'varchar' -NewLength '255' -NewDescription ''

$xml = $doc.OuterXml

$fixedDir = 'worddoc/_tmp_data_dictionary_fixed_v2'
if (Test-Path $fixedDir) {
    Remove-Item -Recurse -Force $fixedDir
}

Copy-Item 'worddoc/_tmp_data_dictionary_orig_recheck' $fixedDir -Recurse
Set-Content -Path (Join-Path $fixedDir 'word/document.xml') -Value $xml -NoNewline

$zipPath = 'worddoc/data_dictionary_fixed.zip'
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Compress-Archive -Path "$fixedDir/*" -DestinationPath $zipPath -Force
Copy-Item $zipPath 'worddoc/data_dictionary_fixed.docx' -Force

Write-Output 'rebuilt'
