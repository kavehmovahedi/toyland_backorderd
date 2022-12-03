import React from "react"
import { Stack } from "@mui/system"
import { CheckCircleRounded } from "@mui/icons-material"
import { Typography } from "@mui/material"

const RegStepThree = (props) => {
    return (
        <Stack spacing={2} textAlign="center" sx={{alignItems: 'center'}}>
            <CheckCircleRounded sx={{fontSize:"40px"}} color="success"/>
            <Typography variant="h2" component='h2'>Welcome!</Typography>
            <Typography>Thank you for registering with us.<br/>
            Your account has been created and will be activated after it is reviewed.<br/>
            We will notify you by email after activation.</Typography>
        </Stack>
    )
}

export default RegStepThree