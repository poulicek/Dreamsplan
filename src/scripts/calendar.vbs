'Returns true if first day of week is Monday
Function vbsFirstDayOfWeekIsMon()
    vbsFirstDayOfWeekIsMon = (WeekDay(DateSerial(2009, 9, 13), 0) = 7)
End Function

'Gets month name
Function vbsMonthName(vbMonthIndex)
    vbsMonthName = MonthName(vbMonthIndex)
End Function

'Gets day of week name
Function vbsWeekDayName(vbDayIndex)
    vbsWeekDayName = WeekDayName(vbDayIndex + 1, true)
End Function

'Gets day of week name
Function vbsWeekNumber(vbYear, vbMonth, vbDay)
    vbsWeekNumber = DatePart("ww", DateSerial(vbYear, vbMonth, vbDay), 0)
End Function